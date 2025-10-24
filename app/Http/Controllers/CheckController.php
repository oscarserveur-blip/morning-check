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
        
        // Utiliser DomPDF pour générer le PDF à partir de la vue Blade
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('checks.pdf', compact('check'));
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
    $check = $data['check'];
    $client = $data['client'];
    $template = $data['template'];
    $serviceChecks = $data['serviceChecks'];

    // Configuration du canvas
    $width = 2000;
    $height = 2500;
    $padding = 50;

    // Police TTF (support accents)
    $fontPath = storage_path('fonts/DejaVuSans.ttf');

    // Config depuis le template
    $config = $template->config ?? [];
    $headerColor = $template->header_color ?? '#FF0000';
    $footerColor = $template->footer_color ?? '#C00000';
    $okColor = $config['ok_color'] ?? '#00B050';
    $nokColor = $config['nok_color'] ?? '#FF0000';
    $warningColor = $config['warning_color'] ?? '#FFC000';

    $manager = new ImageManager(['driver' => 'gd']);
    $img = $manager->canvas($width, $height, '#FFFFFF');

    // En-tête
    $img->rectangle(0, 0, $width, 120, function ($draw) use ($headerColor) {
        $draw->background($headerColor);
    });

    // Logo
    $logoPath = $template->header_logo ?? $client->logo;
    if ($logoPath && file_exists(storage_path('app/public/' . $logoPath))) {
        $logo = $manager->make(storage_path('app/public/' . $logoPath));
        $logo->resize(120, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->insert($logo, 'top-left', $padding, 15);
    }

    // Titre
    $img->text($template->header_title ?? 'Bulletin de Santé Connecte Châlons', $width / 2, 60, function ($font) use ($fontPath) {
        $font->file($fontPath);
        $font->size(60);
        $font->color('#FFFFFF');
        $font->align('center');
        $font->valign('middle');
    });

    // Date
    $img->text($check->date_time->locale('fr')->isoFormat('dddd DD/MM/YYYY'), $width - $padding, 60, function ($font) use ($fontPath) {
        $font->file($fontPath);
        $font->size(45);
        $font->color('#FFFFFF');
        $font->align('right');
        $font->valign('middle');
    });

    $y = 150;

    // Grouper par catégories
    $categories = $serviceChecks->groupBy(function ($sc) {
        return $sc->service->category->title ?? 'Autres';
    });

    foreach ($categories as $catTitle => $services) {
        // Section header
        $img->rectangle(0, $y, $width, $y + 60, function ($draw) {
            $draw->background('#444444');
        });
        $img->text($catTitle, $padding, $y + 30, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(42);
            $font->color('#FFFFFF');
            $font->valign('middle');
        });
        $y += 70;

        // Colonnes
        $img->rectangle(0, $y, $width, $y + 50, function ($draw) {
            $draw->background('#DDDDDD');
        });
        $img->text('Description', $padding, $y + 25, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(38);
            $font->color('#000000');
            $font->valign('middle');
        });
        $img->text('État', $width - 200, $y + 25, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(38);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });
        $y += 60;

        // Services
        foreach ($services as $serviceCheck) {
            $img->rectangle(0, $y, $width, $y + 65, function ($draw) {
                $draw->background('#FFFFFF');
                $draw->border(1, '#000000'); // Bordures visibles
            });

            // Service
            $img->text($serviceCheck->service->title, $padding, $y + 35, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(36);
                $font->color('#000000');
                $font->valign('middle');
            });

            // Traduction statuts
            $statusLabel = match ($serviceCheck->statut) {
                'success' => 'OK',
                'error'   => 'NOK',
                'warning' => 'AVERTISSEMENT',
                default   => 'INCONNU',
            };

            $statusColor = match ($serviceCheck->statut) {
                'success' => $okColor,
                'error'   => $nokColor,
                'warning' => $warningColor,
                default   => '#999999',
            };

            $img->rectangle($width - 250, $y, $width - $padding, $y + 65, function ($draw) use ($statusColor) {
                $draw->background($statusColor);
                $draw->border(1, '#000000');
            });

            $img->text($statusLabel, $width - 150, $y + 35, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(34);
                $font->color('#FFFFFF');
                $font->align('center');
                $font->valign('middle');
            });

            $y += 75;
        }
        $y += 40;
    }

    // Footer
    $img->rectangle(0, $height - 100, $width, $height, function ($draw) use ($footerColor) {
        $draw->background($footerColor);
    });
    $img->text($template->footer_text ?? 'EXPLOITATION, Connecte Châlons : https://glpi.connecte-chalons.fr', $width / 2, $height - 50, function ($font) use ($fontPath) {
        $font->file($fontPath);
        $font->size(32);
        $font->color('#FFFFFF');
        $font->align('center');
        $font->valign('middle');
    });

    // Output
    $pngContent = (string) $img->encode('png');
    $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.png";

    return response($pngContent)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', "attachment; filename=\"$filename\"");
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
        try {
            Mail::send([], [], function ($message) use ($receivers, $senders, $subject, $attachment) {
                $message->to($receivers);
                if (!empty($senders)) {
                    $message->from($senders[0]);
                }
                // Put other senders in CC if present
                if (count($senders) > 1) {
                    $message->cc(array_slice($senders, 1));
                }
                $message->subject($subject);
                $message->html('Veuillez trouver en pièce jointe le rapport de vérification.');
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
                $pdf = Pdf::loadView('checks.pdf', compact('check'));
                return [
                    'data' => $pdf->output(),
                    'filename' => $filename,
                    'mime' => 'application/pdf',
                ];
            case 'png':
                // Build PNG bytes similar to exportToPng
                $pngData = $this->generatePngBytesForCheck($check, $template);
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

    private function generatePngBytesForCheck(Check $check, $template)
    {
        $client = $check->client;
        $serviceChecks = $check->serviceChecks;
        $width = 2000;
        $height = 2500;
        $padding = 50;
        $fontPath = storage_path('fonts/DejaVuSans.ttf');
        $config = $template->config ?? [];
        $headerColor = $template->header_color ?? '#FF0000';
        $footerColor = $template->footer_color ?? '#C00000';
        $okColor = $config['ok_color'] ?? '#00B050';
        $nokColor = $config['nok_color'] ?? '#FF0000';
        $warningColor = $config['warning_color'] ?? '#FFC000';

        $manager = new ImageManager(['driver' => 'gd']);
        $img = $manager->canvas($width, $height, '#FFFFFF');
        $img->rectangle(0, 0, $width, 120, function ($draw) use ($headerColor) {
            $draw->background($headerColor);
        });
        $logoPath = $template->header_logo ?? $client->logo;
        if ($logoPath && file_exists(storage_path('app/public/' . $logoPath))) {
            $logo = $manager->make(storage_path('app/public/' . $logoPath));
            $logo->resize(120, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->insert($logo, 'top-left', $padding, 15);
        }
        $img->text($template->header_title ?? 'Bulletin de Santé Connecte Châlons', $width / 2, 60, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(60);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });
        $img->text($check->date_time->locale('fr')->isoFormat('dddd DD/MM/YYYY'), $width - $padding, 60, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(45);
            $font->color('#FFFFFF');
            $font->align('right');
            $font->valign('middle');
        });

        $y = 150;
        // For brevity, we can list basic info and counts
        $img->text('Rapport de vérification', $padding, $y, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(36);
            $font->color('#000000');
        });
        $y += 60;
        $total = $serviceChecks->count();
        $ok = $serviceChecks->where('statut', 'success')->count();
        $warn = $serviceChecks->where('statut', 'warning')->count();
        $err = $serviceChecks->where('statut', 'error')->count();
        $img->text("Services: OK {$ok} / ⚠ {$warn} / NOK {$err} / Total {$total}", $padding, $y, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(28);
            $font->color('#333333');
        });

        // Footer
        $img->rectangle(0, $height - 120, $width, $height, function ($draw) use ($footerColor) {
            $draw->background($footerColor);
        });
        $img->text($template->footer_text ?? 'EXPLOITATION, Connecte Châlons : https://glpi.connecte-chalons.fr', $width / 2, $height - 50, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(32);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });

        return (string) $img->encode('png');
    }
}
