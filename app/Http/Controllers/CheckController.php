<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\Client;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class CheckController extends Controller
{
    use ManagesUserPermissions;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Check::with(['client', 'creator', 'serviceChecks']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client', function($clientQuery) use ($search) {
                    $clientQuery->where('label', 'like', "%{$search}%");
                })
                ->orWhereHas('creator', function($creatorQuery) use ($search) {
                    $creatorQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('id', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('statut', $request->status);
        }

        // Filtre par client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('date_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_time', '<=', $request->date_to);
        }

        // Tri par date (plus récent en haut par défaut)
        $sortBy = $request->get('sort_by', 'date_time');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Filtrer selon les permissions de l'utilisateur
        $user = auth()->user();
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }

        $perPage = $request->get('per_page', 10);
        $checks = $query->paginate($perPage)->withQueryString();
        
        // Filtrer aussi la liste des clients selon les permissions
        if ($user->isGestionnaire()) {
            $clients = $user->clients()->orderBy('label')->get();
        } else {
            $clients = \App\Models\Client::orderBy('label')->get();
        }

        return view('checks.index', compact('checks', 'clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clients = $user->clients()->with('categories.services')->get();
        } else {
            $clients = Client::with('categories.services')->get();
        }
        
        $selectedClient = null;
        if ($request->filled('client_id')) {
            $selectedClient = $clients->where('id', $request->client_id)->first();
        }
        return view('checks.create', compact('clients', 'selectedClient'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date_time' => [
                'required',
                'date',
                Rule::unique('checks')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                }),
            ],
            'statut' => 'required|in:pending,in_progress,completed,failed',
            'notes' => 'nullable|string|max:1000'
        ]);

        $check = Check::create([
            'client_id' => $validated['client_id'],
            'date_time' => $validated['date_time'],
            'statut' => $validated['statut'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Check créé avec succès',
                'data' => $check
            ]);
        }

        return redirect()->route('clients.show', ['client' => $validated['client_id'], 'tab' => 'checks'])
            ->with('success', 'Check créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Check $check)
    {
        $users = \App\Models\User::all();
        return view('checks.show', compact('check', 'users'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Check $check)
    {
        $check->load(['serviceChecks.service.category', 'client.services.category']);
        $users = \App\Models\User::all();
        return view('checks.edit', compact('check', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Check $check)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date_time' => 'required|date',
            'statut' => 'required|in:pending,in_progress,completed,failed',
            'notes' => 'nullable|string|max:1000',
            'service_checks' => 'nullable|array',
            'service_checks.*.service_id' => 'required|exists:services,id',
            'service_checks.*.statut' => 'required|in:pending,in_progress,success,warning,error',
            'service_checks.*.intervenant' => 'nullable|exists:users,id',
            'service_checks.*.observations' => 'nullable|string|max:1000',
            'service_checks.*.notes' => 'nullable|string|max:1000',
        ]);

        // Mettre à jour le check principal
        $check->update([
            'client_id' => $validated['client_id'],
            'date_time' => $validated['date_time'],
            'statut' => $validated['statut'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Gérer les service checks
        if (isset($validated['service_checks'])) {
            // Supprimer les service checks existants
            $check->serviceChecks()->delete();
            
            // Créer les nouveaux service checks
            foreach ($validated['service_checks'] as $serviceCheckData) {
                $check->serviceChecks()->create([
                    'service_id' => $serviceCheckData['service_id'],
                    'statut' => $serviceCheckData['statut'],
                    'intervenant' => $serviceCheckData['intervenant'] ?? null,
                    'commentaire' => $serviceCheckData['observations'] ?? null,
                    'observations' => $serviceCheckData['observations'] ?? null,
                    'notes' => $serviceCheckData['notes'] ?? null,
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Check mis à jour avec succès',
                'data' => $check->load('serviceChecks')
            ]);
        }

        return redirect()->route('clients.show', ['client' => $check->client_id, 'tab' => 'checks'])
            ->with('success', 'Check mis à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Check $check)
    {
        $clientId = $check->client_id;
        $check->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Check supprimé avec succès'
            ]);
        }

        return redirect()->route('clients.show', ['client' => $clientId, 'tab' => 'checks'])
            ->with('success', 'Check supprimé avec succès');
    }

    /**
     * Export the check using the client's template.
     */
    public function export(Check $check)
    {
        // Vérifier que le client a un template
        if (!$check->client->template) {
            return redirect()->back()->with('error', 'Aucun template associé à ce client.');
        }

        // Vérifier l'état réel des services
        $serviceStats = $check->serviceChecks()->select('statut')->get()->groupBy('statut');
        $total = $check->serviceChecks()->count();
        $pending = isset($serviceStats['pending']) ? $serviceStats['pending']->count() : 0;
        $inProgress = isset($serviceStats['in_progress']) ? $serviceStats['in_progress']->count() : 0;
        if (($pending + $inProgress) === $total) {
            return redirect()->back()->with('error', 'Impossible de télécharger : tous les services sont en attente ou en cours.');
        }
        // On autorise l'export même si certains services sont NOK

        $template = $check->client->template;
        $client = $check->client;

        // Préparer les données pour l'export
        $data = [
            'check' => $check,
            'client' => $client,
            'template' => $template,
            'serviceChecks' => $check->serviceChecks()->with('service.category')->get(),
            'exportDate' => now()->format('d/m/Y H:i'),
            'createdBy' => $check->creator->name ?? 'N/A'
        ];

        // Générer le fichier selon le type de template
        switch ($template->type) {
            case 'excel':
                return $this->exportToExcel($data);
            case 'pdf':
                return $this->exportToPdf($data);
            case 'png':
                return $this->exportToPng($data);
            case 'word':
                return $this->exportToWord($data);
            default:
                return $this->exportToExcel($data); // Par défaut Excel
        }
    }

    /**
     * Export to Excel file.
     */
    private function exportToExcel($data)
    {
        Carbon::setLocale('fr');
        $check = $data['check'];
        $client = $data['client'];
        $template = $data['template'];
        $serviceChecks = $data['serviceChecks'];

        // Utiliser le modèle Excel importé si présent
        if (!empty($template->excel_template) && file_exists(storage_path('app/public/' . $template->excel_template))) {
            $spreadsheet = IOFactory::load(storage_path('app/public/' . $template->excel_template));
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
        }

        // Config avancée
        $config = $template->config ?? [];
        $fontFamily = $config['font']['family'] ?? 'Arial';
        $fontSize = $config['font']['size'] ?? 12;
        $margins = $config['margins'] ?? ['top'=>0,'bottom'=>0,'left'=>0,'right'=>0];
        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName($fontFamily)->setSize($fontSize);
        $sheet->getPageMargins()->setTop($margins['top']/25.4)->setBottom($margins['bottom']/25.4)->setLeft($margins['left']/25.4)->setRight($margins['right']/25.4);

        // Logo (template prioritaire, sinon client)
        $logoPath = null;
        if ($template->header_logo && file_exists(storage_path('app/public/' . $template->header_logo))) {
            $logoPath = storage_path('app/public/' . $template->header_logo);
        } elseif ($client->logo && file_exists(storage_path('app/public/' . $client->logo))) {
            $logoPath = storage_path('app/public/' . $client->logo);
        }
        if ($logoPath) {
            $drawing = new Drawing();
            $drawing->setPath($logoPath);
            $drawing->setHeight(60);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
            
            // Ajuster la hauteur de la première ligne pour le logo
            $sheet->getRowDimension(1)->setRowHeight(65);
        }

        // Header (titre, couleur, date)
        $headerColor = $template->header_color ?? '#FF0000';
        $sheet->mergeCells('C1:H2');
        $sheet->setCellValue('C1', $template->header_title ?? 'Bulletin de Santé');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($headerColor, '#'));
        
        // Date en français
        $frenchDate = $check->date_time->locale('fr')->isoFormat('dddd D MMMM YYYY');
        $sheet->setCellValue('I1', ucfirst($frenchDate));
        $sheet->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        if ($template->description) {
            $sheet->mergeCells('C3:H3');
            $sheet->setCellValue('C3', $template->description);
            $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C3')->getFont()->setItalic(true)->setSize(12);
        }

        $row = 5;
        // Section config (ordre, couleurs)
        $sections = $template->section_config['sections'] ?? [];
        $categories = $serviceChecks->groupBy(function($sc) {
            return $sc->service->category->title ?? 'Autres';
        });
        // Ordonner les sections si config
        $orderedSections = collect($sections)->sortBy('order')->pluck('name')->toArray();
        $catOrder = array_merge($orderedSections, array_diff($categories->keys()->toArray(), $orderedSections));
        foreach ($catOrder as $catTitle) {
            if (!$categories->has($catTitle)) continue;
            $sectionColor = collect($sections)->firstWhere('name', $catTitle)['color'] ?? '444444';
            $sheet->mergeCells("A$row:I$row");
            $sheet->setCellValue("A$row", $catTitle);
            $sheet->getStyle("A$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($sectionColor, '#'));
            $row++;
            $sheet->setCellValue("A$row", 'Description');
            $sheet->setCellValue("I$row", 'Etat');
            $sheet->getStyle("A$row:I$row")->getFont()->setBold(true);
            $sheet->getStyle("A$row:I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
            $sheet->getStyle("A$row:I$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            foreach ($categories[$catTitle] as $sc) {
                $sheet->setCellValue("A$row", $sc->service->title);
                $status = $sc->statut === 'success' ? 'OK' : ($sc->statut === 'error' ? 'NOK' : strtoupper($sc->statut));
                $sheet->setCellValue("I$row", $status);
                // Couleur de fond selon le statut (configurable)
                $okColor = $config['ok_color'] ?? '00B050';
                $nokColor = $config['nok_color'] ?? 'FF0000';
                $warningColor = $config['warning_color'] ?? 'FFC000';
                if ($sc->statut === 'success') {
                    $sheet->getStyle("I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($okColor);
                    $sheet->getStyle("I$row")->getFont()->getColor()->setRGB('FFFFFF');
                } elseif ($sc->statut === 'error') {
                    $sheet->getStyle("I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($nokColor);
                    $sheet->getStyle("I$row")->getFont()->getColor()->setRGB('FFFFFF');
                } elseif ($sc->statut === 'warning') {
                    $sheet->getStyle("I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warningColor);
                    $sheet->getStyle("I$row")->getFont()->getColor()->setRGB('000000');
                }
                $sheet->getStyle("A$row:I$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            $row++;
        }
        // Footer amélioré
        $row++;
        $footerStartRow = $row;
        
        // Ligne de séparation
        $sheet->mergeCells("A$row:I$row");
        $sheet->getStyle("A$row:I$row")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $row++;
        
        // Informations du footer
        $sheet->mergeCells("A$row:I$row");
        $sheet->setCellValue("A$row", "Document généré le " . now()->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm'));
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $row++;
        
        if ($template->footer_text) {
            $sheet->mergeCells("A$row:I$row");
            $sheet->setCellValue("A$row", $template->footer_text);
            $footerColor = $template->footer_color ?? 'C00000';
            $sheet->getStyle("A$row")->getFont()->setBold(true)->getColor()->setRGB(ltrim($footerColor, '#'));
            $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
        
        // Créé par
        $sheet->mergeCells("A$row:I$row");
        $sheet->setCellValue("A$row", "Créé par : " . ($data['createdBy'] ?? 'N/A'));
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Style global du footer
        $sheet->getStyle("A$footerStartRow:I$row")->getFont()->setSize(10);
        
        // Largeur automatique
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Générer le fichier Excel
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();
        $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.xlsx";
        return response($excelOutput)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    /**
     * Export to PDF file.
     */
    private function exportToPdf($data)
    {
        $check = $data['check'];
        // Charger les relations nécessaires pour la vue PDF
        $check->load(['client', 'creator', 'serviceChecks.service.category', 'serviceChecks.intervenantUser']);
        $filename = "check_{$data['client']->label}_{$data['check']->date_time->format('Y-m-d_H-i')}.pdf";
        
        // Générer le HTML via la méthode utilitaire puis le convertir en PDF
        $html = $this->generatePdfContent([
            'template' => $check->client->template,
            'client' => $check->client,
            'check' => $check,
            'serviceChecks' => $check->serviceChecks,
            'exportDate' => now()->format('d/m/Y H:i'),
            'createdBy' => $check->creator->name ?? 'N/A',
        ]);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        return $pdf->download($filename);
    }

    /**
     * Export to Word file.
     */
    private function exportToWord($data)
    {
        $filename = "check_{$data['client']->label}_{$data['check']->date_time->format('Y-m-d_H-i')}.docx";
        
        // Créer le contenu Word basique (vous pouvez utiliser PhpWord)
        $content = $this->generateWordContent($data);
        
        return response($content)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    /**
     * Export to PNG image using Intervention Image (GD) - no external extensions required.
     * The layout follows the template config (colors, sections) similar to the Excel/PDF.
     */




private function exportToPng($data)
{
    try {
        return $this->generatePngImage($data, true);
    } catch (\Throwable $e) {
        return back()->with('error', 'Erreur génération PNG: ' . $e->getMessage());
    }
}

/**
 * Generate PNG image with exact design matching the reference
 */
private function generatePngImage($data, $forDownload = false)
{
    $check = $data['check'];
    $client = $data['client'];
    $template = $data['template'];
    $serviceChecks = $data['serviceChecks']->load('service.category');

    // Configuration du canvas (format A4 portrait)
    $width = 2480;  // A4 à 300 DPI
    $height = 3508;
    $padding = 60;
    $headerHeight = 140;
    $footerHeight = 120;
    $rowHeight = 50;
    $sectionHeaderHeight = 50;
    $subsectionHeaderHeight = 40;

    // Vérifier si les fonctions TTF sont disponibles
    $hasTtfSupport = function_exists('imagettfbbox');
    $fontPath = null;
    if ($hasTtfSupport) {
        $fontPath = storage_path('fonts/DejaVuSans.ttf');
        if (!file_exists($fontPath)) {
            $fontPath = null;
        }
    }

    $config = $template->config ?? [];
    $headerColor = $template->header_color ?? '#FF0000';
    $footerColor = $template->footer_color ?? '#C00000';
    $okColor = $config['ok_color'] ?? '#00B050';
    $nokColor = $config['nok_color'] ?? '#FF0000';
    $warningColor = $config['warning_color'] ?? '#FFC000';
    $sectionBgColor = '#444444';
    $headerBgColor = '#F5F5F5';
    $rowAltColor = '#F9F9F9';

    $manager = new ImageManager(['driver' => 'gd']);
    $img = $manager->canvas($width, $height, '#FFFFFF');

    $y = 0;

    // === HEADER ===
    // Bordure rouge en haut
    $img->rectangle(0, $y, $width, $y + 5, function ($draw) use ($headerColor) {
        $draw->background($headerColor);
    });
    $y += 5;

    // Zone header avec fond blanc
    $headerY = $y;
    $img->rectangle(0, $y, $width, $y + $headerHeight, function ($draw) {
        $draw->background('#FFFFFF');
    });

    // Logo à gauche
    $logoPath = $template->header_logo ?? $client->logo;
    $logoX = $padding;
    if ($logoPath && file_exists(storage_path('app/public/' . $logoPath))) {
        $logo = $manager->make(storage_path('app/public/' . $logoPath));
        $logo->resize(150, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->insert($logo, 'top-left', $logoX, $y + 20);
        $logoX += 180;
    }

    // Titre au centre (rouge)
    $title = $template->header_title ?? 'Bulletin de Santé Connecte Chalons';
    $titleX = $width / 2;
    $titleY = $y + $headerHeight / 2;
    $img->text($title, $titleX, $titleY, function ($font) use ($fontPath, $headerColor) {
        if ($fontPath) $font->file($fontPath);
        $font->size(56);
        $font->color($headerColor);
        $font->align('center');
        $font->valign('middle');
    });

    // Date dans boîte grise à droite
    $dateBoxWidth = 280;
    $dateBoxX = $width - $dateBoxWidth - $padding;
    $dateBoxY = $y + 20;
    $dateBoxHeight = 60;
    $img->rectangle($dateBoxX, $dateBoxY, $dateBoxX + $dateBoxWidth, $dateBoxY + $dateBoxHeight, function ($draw) {
        $draw->background('#444444');
    });
    $frenchDate = $check->date_time->locale('fr')->isoFormat('dddd DD/MM/YYYY');
    $img->text(ucfirst($frenchDate), $dateBoxX + $dateBoxWidth / 2, $dateBoxY + $dateBoxHeight / 2, function ($font) use ($fontPath) {
        if ($fontPath) $font->file($fontPath);
        $font->size(32);
        $font->color('#FFFFFF');
        $font->align('center');
        $font->valign('middle');
    });

    $y += $headerHeight + 30;

    // === CONTENU PRINCIPAL ===
    // Grouper par sections principales (catégories)
    $mainSections = $serviceChecks->groupBy(function ($sc) {
        $category = $sc->service->category ?? null;
        // Regrouper par catégorie parente si disponible
        return $category ? $category->title : 'Autres';
    });

    foreach ($mainSections as $mainSectionTitle => $sectionServices) {
        // === TITRE DE SECTION PRINCIPALE (gris foncé) ===
        $img->rectangle(0, $y, $width, $y + $sectionHeaderHeight, function ($draw) use ($sectionBgColor) {
            $draw->background($sectionBgColor);
        });
        // Bordures (rectangles fins pour simuler des lignes épaisses)
        $borderWidth = 2;
        // Ligne du haut
        $img->rectangle(0, $y, $width, $y + $borderWidth, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne du bas
        $img->rectangle(0, $y + $sectionHeaderHeight - $borderWidth, $width, $y + $sectionHeaderHeight, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne de gauche
        $img->rectangle(0, $y, $borderWidth, $y + $sectionHeaderHeight, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne de droite
        $img->rectangle($width - $borderWidth, $y, $width, $y + $sectionHeaderHeight, function ($draw) {
            $draw->background('#000000');
        });
        $img->text($mainSectionTitle, $padding, $y + $sectionHeaderHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(44);
            $font->color('#FFFFFF');
            $font->valign('middle');
        });
        $y += $sectionHeaderHeight;

        // === EN-TÊTE DE TABLEAU (gris clair) ===
        $img->rectangle(0, $y, $width, $y + $rowHeight, function ($draw) use ($headerBgColor) {
            $draw->background($headerBgColor);
        });
        // Bordures (rectangles fins)
        $borderWidth = 2;
        // Ligne du haut
        $img->rectangle(0, $y, $width, $y + $borderWidth, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne du bas
        $img->rectangle(0, $y + $rowHeight - $borderWidth, $width, $y + $rowHeight, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne de gauche
        $img->rectangle(0, $y, $borderWidth, $y + $rowHeight, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne de droite
        $img->rectangle($width - $borderWidth, $y, $width, $y + $rowHeight, function ($draw) {
            $draw->background('#000000');
        });
        // Ligne verticale séparant Description et État
        $separatorX = $width - 200;
        $img->rectangle($separatorX, $y, $separatorX + $borderWidth, $y + $rowHeight, function ($draw) {
            $draw->background('#000000');
        });
        
        $img->text('Description', $padding + 20, $y + $rowHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(36);
            $font->color('#000000');
            $font->valign('middle');
        });
        $img->text('État', $width - 100, $y + $rowHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(36);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });
        $y += $rowHeight;

        // Grouper par sous-sections si nécessaire (ex: Applications, INFORMATIQUE)
        $subSections = $sectionServices->groupBy(function ($sc) {
            // Pour l'instant, pas de sous-section, on liste directement les services
            return 'all';
        });

        foreach ($subSections as $subSectionTitle => $services) {
            // Si sous-section (ex: "Applications", "INFORMATIQUE"), ajouter un titre
            if ($subSectionTitle !== 'all') {
                $img->rectangle(0, $y, $width, $y + $subsectionHeaderHeight, function ($draw) {
                    $draw->background('#E8E8E8');
                });
                $img->text($subSectionTitle, $padding + 20, $y + $subsectionHeaderHeight / 2, function ($font) use ($fontPath) {
                    if ($fontPath) $font->file($fontPath);
                    $font->size(38);
                    $font->color('#000000');
                    $font->valign('middle');
                    $font->bold(true);
                });
                $y += $subsectionHeaderHeight;
            }

            // Services dans cette sous-section
            $rowIndex = 0;
            foreach ($services as $serviceCheck) {
                $bgColor = ($rowIndex % 2 === 0) ? '#FFFFFF' : $rowAltColor;
                
                // Ligne de service
                $img->rectangle(0, $y, $width, $y + $rowHeight, function ($draw) use ($bgColor) {
                    $draw->background($bgColor);
                });
                // Bordures (rectangles fins)
                $cellBorderWidth = 1;
                // Ligne du haut
                $img->rectangle(0, $y, $width, $y + $cellBorderWidth, function ($draw) {
                    $draw->background('#000000');
                });
                // Ligne du bas
                $img->rectangle(0, $y + $rowHeight - $cellBorderWidth, $width, $y + $rowHeight, function ($draw) {
                    $draw->background('#000000');
                });
                // Ligne de gauche
                $img->rectangle(0, $y, $cellBorderWidth, $y + $rowHeight, function ($draw) {
                    $draw->background('#000000');
                });
                // Ligne de droite
                $img->rectangle($width - $cellBorderWidth, $y, $width, $y + $rowHeight, function ($draw) {
                    $draw->background('#000000');
                });
                // Ligne verticale séparant Description et État
                $separatorX = $width - 200;
                $img->rectangle($separatorX, $y, $separatorX + $cellBorderWidth, $y + $rowHeight, function ($draw) {
                    $draw->background('#000000');
                });

                // Description du service
                $img->text($serviceCheck->service->title ?? 'N/A', $padding + 20, $y + $rowHeight / 2, function ($font) use ($fontPath) {
                    if ($fontPath) $font->file($fontPath);
                    $font->size(34);
                    $font->color('#000000');
                    $font->valign('middle');
                });

                // Statut
                $statusLabel = match ($serviceCheck->statut) {
                    'success' => 'OK',
                    'error'   => 'NOK',
                    'warning' => 'AVERTISSEMENT',
                    default   => strtoupper($serviceCheck->statut ?? 'INCONNU'),
                };

                $statusColor = match ($serviceCheck->statut) {
                    'success' => $okColor,
                    'error'   => $nokColor,
                    'warning' => $warningColor,
                    default   => '#999999',
                };

                // Rectangle pour le statut
                $statusX = $width - 200;
                $statusWidth = 200;
                $img->rectangle($statusX, $y, $statusX + $statusWidth, $y + $rowHeight, function ($draw) use ($statusColor) {
                    $draw->background($statusColor);
                });
                $img->text($statusLabel, $statusX + $statusWidth / 2, $y + $rowHeight / 2, function ($font) use ($fontPath) {
                    if ($fontPath) $font->file($fontPath);
                    $font->size(32);
                    $font->color('#FFFFFF');
                    $font->align('center');
                    $font->valign('middle');
                });

                $y += $rowHeight;
                $rowIndex++;
            }
        }
        $y += 20; // Espace entre sections
    }

    // === FOOTER ===
    $footerY = $height - $footerHeight;
    $img->rectangle(0, $footerY, $width, $height, function ($draw) use ($footerColor) {
        $draw->background($footerColor);
    });
    
    $footerText = $template->footer_text ?? 'EXPLOITATION, Connecte Châlons : https://glpi.connecte-chalons.fr';
    $footerLines = explode("\n", $footerText);
    $lineHeight = 35;
    $startY = $footerY + 30;
    
    foreach ($footerLines as $index => $line) {
        $img->text($line, $width / 2, $startY + ($index * $lineHeight), function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(28);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });
    }

    // Output
    $pngContent = (string) $img->encode('png');
    
    if ($forDownload) {
        $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.png";
        return response($pngContent)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }
    
    return $pngContent;
}

    /**
     * Generate Excel content.
     */
    private function generateExcelContent($data)
    {
        $template = $data['template'];
        $client = $data['client'];
        $check = $data['check'];
        
        // Créer un fichier CSV simple (format Excel compatible)
        $csv = [];
        
        // En-tête avec les informations du template
        if ($template->header_title) {
            $csv[] = [$template->header_title];
            $csv[] = []; // Ligne vide
        }
        
        // Informations du client
        $csv[] = ['Client:', $client->label];
        $csv[] = ['Date de vérification:', $check->date_time->format('d/m/Y H:i')];
        $csv[] = ['Statut:', $this->getStatusLabel($check->statut)];
        $csv[] = ['Créé par:', $data['createdBy']];
        $csv[] = ['Date d\'export:', $data['exportDate']];
        $csv[] = []; // Ligne vide
        
        // Services vérifiés
        if ($data['serviceChecks']->count() > 0) {
            $csv[] = ['Services vérifiés'];
            $csv[] = ['Service', 'Catégorie', 'Statut', 'Notes', 'Date'];
            
            foreach ($data['serviceChecks'] as $serviceCheck) {
                $csv[] = [
                    $serviceCheck->service->title ?? 'N/A',
                    $serviceCheck->service->category->title ?? 'N/A',
                    $this->getServiceStatusLabel($serviceCheck->status),
                    $serviceCheck->notes ?? '',
                    $serviceCheck->created_at->format('d/m/Y H:i')
                ];
            }
        } else {
            $csv[] = ['Aucun service vérifié'];
        }
        
        // Pied de page
        if ($template->footer_text) {
            $csv[] = []; // Ligne vide
            $csv[] = [$template->footer_text];
        }
        
        // Convertir en CSV
        $output = '';
        foreach ($csv as $row) {
            $output .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }
        
        return $output;
    }

    /**
     * Generate PDF content.
     */
    private function generatePdfContent($data)
    {
        $template = $data['template'];
        $client = $data['client'];
        $check = $data['check'];
        
        // Créer un contenu HTML simple pour le PDF
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Rapport de vérification</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: ' . ($template->header_color ?: '#333') . '; }
                .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .info-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                .info-table td:first-child { font-weight: bold; width: 200px; }
                .services-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .services-table th, .services-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                .services-table th { background-color: #f5f5f5; }
                .status-success { color: green; }
                .status-warning { color: orange; }
                .status-error { color: red; }
                .footer { text-align: center; margin-top: 30px; color: ' . ($template->footer_color ?: '#666') . '; }
            </style>
        </head>
        <body>';
        
        // En-tête
        if ($template->header_title) {
            $html .= '<div class="header"><h1>' . htmlspecialchars($template->header_title) . '</h1></div>';
        }
        
        // Informations du check
        $html .= '<table class="info-table">
            <tr><td>Client:</td><td>' . htmlspecialchars($client->label) . '</td></tr>
            <tr><td>Date de vérification:</td><td>' . $check->date_time->format('d/m/Y H:i') . '</td></tr>
            <tr><td>Statut:</td><td>' . $this->getStatusLabel($check->statut) . '</td></tr>
            <tr><td>Créé par:</td><td>' . htmlspecialchars($data['createdBy']) . '</td></tr>
            <tr><td>Date d\'export:</td><td>' . $data['exportDate'] . '</td></tr>
        </table>';
        
        // Services vérifiés
        if ($data['serviceChecks']->count() > 0) {
            $html .= '<h3>Services vérifiés</h3>
            <table class="services-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Catégorie</th>
                        <th>Statut</th>
                        <th>Notes</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($data['serviceChecks'] as $serviceCheck) {
                $statusClass = $this->getServiceStatusClass($serviceCheck->status);
                $html .= '<tr>
                    <td>' . htmlspecialchars($serviceCheck->service->title ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($serviceCheck->service->category->title ?? 'N/A') . '</td>
                    <td class="' . $statusClass . '">' . $this->getServiceStatusLabel($serviceCheck->status) . '</td>
                    <td>' . htmlspecialchars($serviceCheck->notes ?? '') . '</td>
                    <td>' . $serviceCheck->created_at->format('d/m/Y H:i') . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>Aucun service vérifié</p>';
        }
        
        // Pied de page
        if ($template->footer_text) {
            $html .= '<div class="footer">' . htmlspecialchars($template->footer_text) . '</div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Generate Word content.
     */
    private function generateWordContent($data)
    {
        // Pour l'instant, retourner le même contenu que PDF
        // Vous pouvez implémenter PhpWord pour un vrai fichier Word
        return $this->generatePdfContent($data);
    }

    /**
     * Get status label.
     */
    private function getStatusLabel($status)
    {
        return match($status) {
            'completed' => 'Terminé',
            'pending' => 'En attente',
            'failed' => 'Échoué',
            default => $status
        };
    }

    /**
     * Get service status label.
     */
    private function getServiceStatusLabel($status)
    {
        return match($status) {
            'success' => 'Succès',
            'warning' => 'Avertissement',
            'error' => 'Échec',
            default => $status
        };
    }

    /**
     * Get service status CSS class.
     */
    private function getServiceStatusClass($status)
    {
        return match($status) {
            'success' => 'status-success',
            'warning' => 'status-warning',
            'error' => 'status-error',
            default => ''
        };
    }

    public function autoCheck(\App\Models\Client $client)
    {
        $today = now()->startOfDay();
        $userId = auth()->id() ?? 1;
        // Vérifier s'il existe déjà un check pour aujourd'hui
        $existing = $client->checks()->whereDate('date_time', $today)->first();
        if ($existing) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un check existe déjà pour aujourd\'hui.',
                    'check_id' => $existing->id
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Un check existe déjà pour aujourd\'hui.',
                'check_id' => $existing->id
            ]);
        }
        // Créer le check
        $check = $client->checks()->create([
            'date_time' => now(),
            'statut' => 'pending',
            'created_by' => $userId,
        ]);
        $services = $client->services;
        foreach ($services as $service) {
            $check->serviceChecks()->create([
                'service_id' => $service->id,
                'statut' => 'pending',
            ]);
        }
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Check automatique créé avec succès.',
                'check_id' => $check->id
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Check automatique créé avec succès.',
            'check_id' => $check->id
        ]);
    }

    /**
     * Check if a check can be downloaded.
     */
    public function checkStatus(Check $check)
    {
        $serviceStats = $check->serviceChecks()->select('statut')->get()->groupBy('statut');
        $canDownload = true;

        // Interdire le téléchargement uniquement si TOUS les services sont en pending ou in_progress
        $total = $check->serviceChecks()->count();
        $pending = isset($serviceStats['pending']) ? $serviceStats['pending']->count() : 0;
        $inProgress = isset($serviceStats['in_progress']) ? $serviceStats['in_progress']->count() : 0;
        if (($pending + $inProgress) === $total) {
            $canDownload = false;
        }

        return response()->json([
            'can_download' => $canDownload,
            'status' => $check->statut,
            'message' => $canDownload ? 'Le check peut être téléchargé.' : 'Impossible de télécharger tant que tous les services sont en attente ou en cours.'
        ]);
    }
    
    /**
     * Send the check report by email to client's mailings.
     */
    public function send(Check $check)
    {
        // Collect recipients from client's mailings
        $client = $check->client()->with('mailings')->first();
        if (!$client) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Client introuvable.'], 404);
            }
            return back()->with('error', 'Client introuvable.');
        }

        $receivers = $client->mailings()->whereIn('type', ['receiver', 'copie'])->pluck('email')->toArray();
        $senders = $client->mailings()->where('type', 'sender')->pluck('email')->toArray();

        if (empty($receivers)) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Aucun destinataire email défini pour ce client."], 422);
            }
            return back()->with('error', "Aucun destinataire email défini pour ce client.");
        }

        // Ensure exportable state similar to download gate
        $serviceStats = $check->serviceChecks()->select('statut')->get()->groupBy('statut');
        $total = $check->serviceChecks()->count();
        $pending = isset($serviceStats['pending']) ? $serviceStats['pending']->count() : 0;
        $inProgress = isset($serviceStats['in_progress']) ? $serviceStats['in_progress']->count() : 0;
        if (($pending + $inProgress) === $total) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Impossible d\'envoyer : tous les services sont en attente ou en cours.'], 422);
            }
            return back()->with('error', 'Impossible d\'envoyer : tous les services sont en attente ou en cours.');
        }

        // Build attachment based on client's template type
        $attachment = $this->buildCheckAttachment($check);
        if ($attachment === null) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Type de template non pris en charge pour l'envoi."], 422);
            }
            return back()->with('error', "Type de template non pris en charge pour l'envoi.");
        }

        // Prepare and send email
        $subject = "Rapport de vérification - {$client->label} - " . $check->date_time->format('d/m/Y H:i');
        
        // Générer le HTML de l'email selon le type de template
        $emailHtml = $this->generateEmailHtml($check, $client, $attachment);
        
        try {
            Mail::send([], [], function ($message) use ($receivers, $senders, $subject, $attachment, $emailHtml) {
                $message->to($receivers);
                if (!empty($senders)) {
                    $message->from($senders[0]);
                }
                // Put other senders in CC if present
                if (count($senders) > 1) {
                    $message->cc(array_slice($senders, 1));
                }
                $message->subject($subject);
                $message->html($emailHtml);
                $message->attachData($attachment['data'], $attachment['filename'], [
                    'mime' => $attachment['mime'],
                ]);
            });
        } catch (\Throwable $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Échec de l'envoi de l'email: " . $e->getMessage()], 500);
            }
            return back()->with('error', "Échec de l'envoi de l'email: " . $e->getMessage());
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès.'
            ]);
        }
        
        return back()->with('success', 'Email envoyé avec succès.');
    }

    /**
     * Build the appropriate attachment (bytes, filename, mime) for a check based on client's template type.
     */
    private function buildCheckAttachment(Check $check): ?array
    {
        $check->load(['client', 'creator', 'serviceChecks.service.category', 'serviceChecks.intervenantUser']);
        $client = $check->client;
        $template = $client->template;
        $type = $template->type ?? 'excel';

        switch ($type) {
            case 'pdf':
                $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.pdf";
                $html = $this->generatePdfContent([
                    'template' => $template,
                    'client' => $client,
                    'check' => $check,
                    'serviceChecks' => $check->serviceChecks,
                    'exportDate' => now()->format('d/m/Y H:i'),
                    'createdBy' => $check->creator->name ?? 'N/A',
                ]);
                $pdf = Pdf::loadHTML($html);
                return [
                    'data' => $pdf->output(),
                    'filename' => $filename,
                    'mime' => 'application/pdf',
                ];
            case 'png':
                // Build PNG bytes using the same function as exportToPng
                $data = [
                    'check' => $check,
                    'client' => $client,
                    'template' => $template,
                    'serviceChecks' => $check->serviceChecks,
                ];
                $pngData = $this->generatePngImage($data, false);
                $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.png";
                return [
                    'data' => $pngData,
                    'filename' => $filename,
                    'mime' => 'image/png',
                ];
            case 'word':
                $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.docx";
                $content = $this->generateWordContent([
                    'template' => $template,
                    'client' => $client,
                    'check' => $check,
                    'serviceChecks' => $check->serviceChecks,
                    'exportDate' => now()->format('d/m/Y H:i'),
                    'createdBy' => $check->creator->name ?? 'N/A',
                ]);
                return [
                    'data' => $content,
                    'filename' => $filename,
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ];
            case 'excel':
            default:
                // Reuse exportToExcel logic to produce XLSX bytes
                $data = [
                    'check' => $check,
                    'client' => $client,
                    'template' => $template,
                    'serviceChecks' => $check->serviceChecks,
                    'exportDate' => now()->format('d/m/Y H:i'),
                    'createdBy' => $check->creator->name ?? 'N/A'
                ];
                $bytesAndName = $this->generateExcelBytesAndFilename($data);
                return [
                    'data' => $bytesAndName['bytes'],
                    'filename' => $bytesAndName['filename'],
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ];
        }
    }

    private function generateExcelBytesAndFilename(array $data): array
    {
        // Replicate exportToExcel content but return bytes and filename
        $template = $data['template'];
        $client = $data['client'];
        $check = $data['check'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rapport de vérification');
        // Minimal header
        $sheet->setCellValue('A1', $template->header_title ?: 'Rapport de vérification');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A3', 'Client:');
        $sheet->setCellValue('B3', $client->label);
        $sheet->setCellValue('A4', 'Date de vérification:');
        $sheet->setCellValue('B4', $check->date_time->format('d/m/Y H:i'));
        $sheet->setCellValue('A5', 'Créé par:');
        $sheet->setCellValue('B5', $data['createdBy']);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();
        $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.xlsx";
        return ['bytes' => $excelOutput, 'filename' => $filename];
    }


    /**
     * Generate HTML email content based on template type
     */
    private function generateEmailHtml(Check $check, Client $client, array $attachment): string
    {
        try {
            $client->load('template');
            $template = $client->template;
            
            if (!$template) {
                return $this->generateSimpleEmailHtml($check, $client);
            }
            
            $serviceChecks = $check->serviceChecks()->with('service.category')->get();
            
            // Si le type est PNG, on génère un HTML similaire à l'exemple
            if (($template->type ?? 'excel') === 'png') {
                return $this->generatePngEmailHtml($check, $client, $template, $serviceChecks);
            }
            
            // Pour les autres types, HTML simple
            return $this->generateSimpleEmailHtml($check, $client);
        } catch (\Throwable $e) {
            // En cas d'erreur, retourner un HTML simple
            \Log::error('Erreur génération HTML email: ' . $e->getMessage());
            return $this->generateSimpleEmailHtml($check, $client);
        }
    }

    /**
     * Generate HTML email for PNG template (style bulletin de santé)
     */
    private function generatePngEmailHtml(Check $check, Client $client, $template, $serviceChecks): string
    {
        $headerColor = $template->header_color ?? '#0b5aa0';
        $frenchDate = $check->date_time->locale('fr')->isoFormat('dddd D MMMM YYYY');
        
        // S'assurer que toutes les relations sont chargées
        $serviceChecks->load(['service.category']);
        
        // Grouper par catégories
        $categories = $serviceChecks->groupBy(function ($sc) {
            if ($sc->service && $sc->service->category) {
                return $sc->service->category->title ?? 'Autres';
            }
            return 'Autres';
        });
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: ' . $headerColor . '; color: #ffffff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .header .date { margin-top: 10px; font-size: 14px; }
        .content { padding: 20px; }
        .category { margin-bottom: 30px; }
        .category-title { background-color: ' . $headerColor . '; color: #ffffff; padding: 10px 15px; font-weight: bold; font-size: 16px; margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        table th { background-color: #f5f5f5; padding: 12px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
        table td { padding: 12px; border: 1px solid #ddd; }
        .status-ok { background-color: #00B050; color: #ffffff; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status-nok { background-color: #FF0000; color: #ffffff; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status-warning { background-color: #FFC000; color: #000000; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .footer { background-color: ' . ($template->footer_color ?? '#C00000') . '; color: #ffffff; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($template->header_title ?? 'Bulletin de Santé IT') . '</h1>
            <div class="date">' . ucfirst($frenchDate) . '</div>
        </div>
        <div class="content">';
        
        foreach ($categories as $catTitle => $services) {
            $html .= '<div class="category">
                <div class="category-title">' . htmlspecialchars($catTitle) . '</div>
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>État</th>
                            <th>Observations</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($services as $sc) {
                if (!$sc->service) {
                    continue;
                }
                
                $statusClass = match($sc->statut) {
                    'success' => 'status-ok',
                    'error' => 'status-nok',
                    'warning' => 'status-warning',
                    default => ''
                };
                
                $statusLabel = match($sc->statut) {
                    'success' => 'OK',
                    'error' => 'NOK',
                    'warning' => 'AVERTISSEMENT',
                    default => strtoupper($sc->statut ?? 'INCONNU')
                };
                
                $observations = $sc->observations ?? $sc->notes ?? '';
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($sc->service->title ?? 'N/A') . '</td>
                    <td><span class="' . $statusClass . '">' . $statusLabel . '</span></td>
                    <td>' . htmlspecialchars($observations) . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        $html .= '</div>
        <div class="footer">' . htmlspecialchars($template->footer_text ?? 'EXPLOITATION, Connecte Châlons : https://glpi.connecte-chalons.fr') . '</div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Generate simple HTML email for other template types
     */
    private function generateSimpleEmailHtml(Check $check, Client $client): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="content">
        <h2>Rapport de vérification - ' . htmlspecialchars($client->label) . '</h2>
        <p>Date: ' . $check->date_time->format('d/m/Y H:i') . '</p>
        <p>Veuillez trouver en pièce jointe le rapport de vérification.</p>
    </div>
</body>
</html>';
    }
}
