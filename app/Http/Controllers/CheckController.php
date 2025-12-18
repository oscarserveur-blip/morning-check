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

        // Préparer les données pour l'export avec les catégories parent (charger récursivement)
        $serviceChecks = $check->serviceChecks()->with(['service.category' => function($query) {
            $query->with('parent');
        }])->get();
        
        // Charger récursivement tous les parents
        foreach ($serviceChecks as $serviceCheck) {
            if ($serviceCheck->service && $serviceCheck->service->category) {
                $category = $serviceCheck->service->category;
                $parent = $category->parent;
                while ($parent) {
                    $parent->load('parent');
                    $parent = $parent->parent;
                }
            }
        }
        
        $data = [
            'check' => $check,
            'client' => $client,
            'template' => $template,
            'serviceChecks' => $serviceChecks,
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
        // Section config (ordre, couleurs) - Groupé par catégories parent
        $sections = $template->section_config['sections'] ?? [];
        
        // Grouper par catégorie parent
        $categories = $serviceChecks->groupBy(function($sc) {
            $category = $sc->service->category ?? null;
            if ($category && $category->parent) {
                return $category->parent->title;
            }
            return $category ? $category->title : 'Autres';
        });
        
        // Ordonner les sections si config
        $orderedSections = collect($sections)->sortBy('order')->pluck('name')->toArray();
        $catOrder = array_merge($orderedSections, array_diff($categories->keys()->toArray(), $orderedSections));
        
        foreach ($catOrder as $catTitle) {
            if (!$categories->has($catTitle)) continue;
            
            // Obtenir la catégorie parent pour cette section
            $firstServiceCheck = $categories[$catTitle]->first();
            $parentCategory = $firstServiceCheck->service->category->parent ?? $firstServiceCheck->service->category ?? null;
            
            // Obtenir la configuration des colonnes pour cette catégorie spécifique
            $exportColumns = $this->getExportColumns($template, $client, $parentCategory);
            
            // Calculer le nombre de colonnes nécessaires pour cette catégorie
            $columnCount = count($exportColumns);
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
            
            $sectionColor = collect($sections)->firstWhere('name', $catTitle)['color'] ?? '444444';
            $sheet->mergeCells("A$row:{$lastColumn}$row");
            $sheet->setCellValue("A$row", $catTitle);
            $sheet->getStyle("A$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($sectionColor, '#'));
            $row++;
            
            // Afficher les statistiques si configuré pour cette catégorie
            if ($parentCategory && $parentCategory->show_stats) {
                $stats = $this->calculateCategoryStats($categories[$catTitle], $parentCategory);
                if (!empty($stats)) {
                    $sheet->mergeCells("A$row:{$lastColumn}$row");
                    $html = '<table style="width:100%;"><tr>';
                    foreach ($stats as $stat) {
                        $html .= "<td><strong>{$stat['label']}:</strong> {$stat['value']}</td>";
                    }
                    $html .= '</tr></table>';
                    $sheet->setCellValue("A$row", $this->formatStatsForExcel($stats));
                    $sheet->getStyle("A$row")->getFont()->setBold(true);
                    $row++;
                }
            }
            
            // En-têtes de colonnes selon la configuration
            $colIndex = 1;
            foreach ($exportColumns as $column) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue("{$colLetter}$row", $column['label']);
                $colIndex++;
            }
            
            $sheet->getStyle("A$row:{$lastColumn}$row")->getFont()->setBold(true);
            $sheet->getStyle("A$row:{$lastColumn}$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
            $sheet->getStyle("A$row:{$lastColumn}$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            foreach ($categories[$catTitle] as $sc) {
                $category = $sc->service->category ?? null;
                $colIndex = 1;
                foreach ($exportColumns as $column) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $value = $this->getColumnValue($sc, $column['field'], $category);
                    
                    $sheet->setCellValue("{$colLetter}$row", $value);
                    
                    // Appliquer les styles spécifiques selon le type de colonne
                    if ($column['field'] === 'expiration_date' && $sc->expiration_date) {
                        $daysUntilExpiration = now()->diffInDays($sc->expiration_date, false);
                        if ($daysUntilExpiration < 0) {
                            $sheet->getStyle("{$colLetter}$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFEBEE');
                            $sheet->getStyle("{$colLetter}$row")->getFont()->setBold(true)->getColor()->setRGB('C62828');
                        } elseif ($daysUntilExpiration <= 30) {
                            $sheet->getStyle("{$colLetter}$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3E0');
                            $sheet->getStyle("{$colLetter}$row")->getFont()->getColor()->setRGB('E65100');
                        }
                    } elseif ($column['field'] === 'statut') {
                        // Couleur de fond selon le statut
                        $okColor = $config['ok_color'] ?? '00B050';
                        $nokColor = $config['nok_color'] ?? 'FF0000';
                        $warningColor = $config['warning_color'] ?? 'FFC000';
                        if ($sc->statut === 'success') {
                            $sheet->getStyle("{$colLetter}$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($okColor);
                            $sheet->getStyle("{$colLetter}$row")->getFont()->getColor()->setRGB('FFFFFF');
                        } elseif ($sc->statut === 'error') {
                            $sheet->getStyle("{$colLetter}$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($nokColor);
                            $sheet->getStyle("{$colLetter}$row")->getFont()->getColor()->setRGB('FFFFFF');
                        } elseif ($sc->statut === 'warning') {
                            $sheet->getStyle("{$colLetter}$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warningColor);
                            $sheet->getStyle("{$colLetter}$row")->getFont()->getColor()->setRGB('000000');
                        }
                    }
                    
                    $colIndex++;
                }
                
                $sheet->getStyle("A$row:{$lastColumn}$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            $row++;
        }
        // Footer amélioré
        $row++;
        $footerStartRow = $row;
        
        // Ligne de séparation
        $sheet->mergeCells("A$row:{$lastColumn}$row");
        $sheet->getStyle("A$row:{$lastColumn}$row")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $row++;
        
        // Informations du footer
        $sheet->mergeCells("A$row:{$lastColumn}$row");
        $sheet->setCellValue("A$row", "Document généré le " . now()->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm'));
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $row++;
        
        if ($template->footer_text) {
            $sheet->mergeCells("A$row:{$lastColumn}$row");
            $sheet->setCellValue("A$row", $template->footer_text);
            $footerColor = $template->footer_color ?? 'C00000';
            $sheet->getStyle("A$row")->getFont()->setBold(true)->getColor()->setRGB(ltrim($footerColor, '#'));
            $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
        
        // Créé par
        $sheet->mergeCells("A$row:{$lastColumn}$row");
        $sheet->setCellValue("A$row", "Créé par : " . ($data['createdBy'] ?? 'N/A'));
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Style global du footer
        $sheet->getStyle("A$footerStartRow:{$lastColumn}$row")->getFont()->setSize(10);
        
        // Largeur automatique pour toutes les colonnes (on prend le max de toutes les catégories)
        $maxColumns = 0;
        foreach ($categories as $catTitle => $serviceChecks) {
            $firstServiceCheck = $serviceChecks->first();
            $parentCategory = $firstServiceCheck->service->category->parent ?? $firstServiceCheck->service->category ?? null;
            $exportColumns = $this->getExportColumns($template, $client, $parentCategory);
            $maxColumns = max($maxColumns, count($exportColumns));
        }
        for ($i = 1; $i <= $maxColumns; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
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
 * Validate and normalize hex color
 */
private function normalizeColor($color, $default = '#000000')
{
    if (empty($color)) {
        return $default;
    }
    
    // Remove # if present
    $color = ltrim($color, '#');
    
    // Validate hex color (3 or 6 characters)
    if (!preg_match('/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/', $color)) {
        return $default;
    }
    
    // Normalize to 6 characters
    if (strlen($color) === 3) {
        $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
    }
    
    return '#' . strtoupper($color);
}

/**
 * Generate PNG image with exact design matching the reference
 */
private function generatePngImage($data, $forDownload = false)
{
    try {
        $check = $data['check'];
        $client = $data['client'];
        $template = $data['template'];
        $serviceChecks = $data['serviceChecks']->load('service.category');
        
        // Charger tous les intervenants en une seule requête
        $intervenantIds = $serviceChecks->pluck('intervenant')->filter()->unique();
        $intervenants = \App\Models\User::whereIn('id', $intervenantIds)->get()->keyBy('id');

    // Configuration du canvas (format A4 portrait)
    $width = 2480;  // A4 à 300 DPI
    $height = 3508;
    $padding = 60;
    $headerHeight = 180;  // Plus haut pour logo + titre
    $footerHeight = 140;  // Plus haut pour 2 lignes de texte
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
    // Normaliser et valider toutes les couleurs
    $headerColor = $this->normalizeColor($template->header_color ?? '#FF0000', '#FF0000');
    $footerColor = $this->normalizeColor($template->footer_color ?? '#C00000', '#C00000');
    
    // Les couleurs dans config peuvent être stockées avec ou sans #
    $okColor = $this->normalizeColor($config['ok_color'] ?? '#00B050', '#00B050');
    $nokColor = $this->normalizeColor($config['nok_color'] ?? '#FF0000', '#FF0000');
    $warningColor = $this->normalizeColor($config['warning_color'] ?? '#FFC000', '#FFC000');
    
    // Couleurs pour les sections et tableaux
    $sectionBgColor = '#333333';  // Gris foncé pour les en-têtes de sections
    $headerBgColorDark = '#333333';  // Gris foncé pour les en-têtes de colonnes
    $rowAltColor = '#F9F9F9';  // Gris clair pour les lignes alternées

    $manager = new ImageManager(['driver' => 'gd']);
    $img = $manager->canvas($width, $height, '#FFFFFF');

    // Helper function pour créer un rectangle rempli
    $drawFilledRect = function($x, $y, $w, $h, $color) use ($manager, $img) {
        $rect = $manager->canvas($w, $h, $color);
        $img->insert($rect, 'top-left', $x, $y);
    };

    $y = 0;

    // === HEADER ===
    // Header avec fond blanc (déjà fait par le canvas)
    // Bordure rouge autour du header en utilisant fill()
    $borderWidth = 3;
    // Remplir les bordures avec fill() - GD supporte fill() avec un point
    // Bordure du haut
    $borderTop = $manager->canvas($width, $borderWidth, $headerColor);
    $img->insert($borderTop, 'top-left', 0, $y);
    // Bordure du bas
    $borderBottom = $manager->canvas($width, $borderWidth, $headerColor);
    $img->insert($borderBottom, 'top-left', 0, $y + $headerHeight - $borderWidth);
    // Bordure gauche
    $borderLeft = $manager->canvas($borderWidth, $headerHeight, $headerColor);
    $img->insert($borderLeft, 'top-left', 0, $y);
    // Bordure droite
    $borderRight = $manager->canvas($borderWidth, $headerHeight, $headerColor);
    $img->insert($borderRight, 'top-left', $width - $borderWidth, $y);

    // Logo à gauche avec texte "CONNECTE CHALONS"
    $logoX = $padding + 20;
    $logoY = $y + 30;
    $logoPath = $template->header_logo ?? $client->logo;
    if ($logoPath && file_exists(storage_path('app/public/' . $logoPath))) {
        try {
            $logo = $manager->make(storage_path('app/public/' . $logoPath));
            $logo->resize(120, 120, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->insert($logo, 'top-left', $logoX, $logoY);
        } catch (\Exception $e) {
            \Log::warning('Erreur chargement logo: ' . $e->getMessage());
        }
    }
    // Texte "CONNECTE CHALONS" à gauche du logo
    $img->text('CONNECTE CHALONS', $logoX, $logoY - 10, function ($font) use ($fontPath) {
        if ($fontPath) $font->file($fontPath);
        $font->size(28);
        $font->color('#333333');
        $font->align('left');
        $font->valign('top');
    });

    // Titre au centre en ROUGE
    $title = $template->header_title ?? 'Bulletin de Santé Connecte Chalons';
    $titleX = $width / 2;
    $titleY = $y + $headerHeight / 2;
    $img->text($title, $titleX, $titleY, function ($font) use ($fontPath) {
        if ($fontPath) $font->file($fontPath);
        $font->size(52);
        $font->color('#FF0000');
        $font->align('center');
        $font->valign('middle');
    });

    // Date à droite dans une boîte grise foncée
    $frenchDate = $check->date_time->locale('fr')->isoFormat('dddd DD/MM/YYYY');
    $dateBoxWidth = 300;
    $dateBoxHeight = 60;
    $dateBoxX = $width - $padding - $dateBoxWidth - 20;
    $dateBoxY = $y + ($headerHeight - $dateBoxHeight) / 2;
    $dateBgColor = '#333333';
    $drawFilledRect($dateBoxX, $dateBoxY, $dateBoxWidth, $dateBoxHeight, $dateBgColor);
    $img->text(ucfirst($frenchDate), $dateBoxX + $dateBoxWidth / 2, $dateBoxY + $dateBoxHeight / 2, function ($font) use ($fontPath) {
        if ($fontPath) $font->file($fontPath);
        $font->size(32);
        $font->color('#FFFFFF');
        $font->align('center');
        $font->valign('middle');
    });

    $y += $headerHeight + 20;

    // === CONTENU PRINCIPAL ===
    // Toujours utiliser 3 colonnes : Description | État | Observations
    $colDescriptionWidth = intval($width * 0.60);  // 60% pour Description
    $colEtatWidth = intval($width * 0.20);        // 20% pour État
    $colObservationsWidth = $width - $colDescriptionWidth - $colEtatWidth; // 20% pour Observations
    $separator1X = $colDescriptionWidth;
    $separator2X = $colDescriptionWidth + $colEtatWidth;
    
    $cellPadding = 20;  // Padding uniforme pour toutes les cellules
    $borderWidth = 2;   // Épaisseur bordures principales (extérieures)
    $cellBorderWidth = 1; // Épaisseur bordures cellules (intérieures)
    
    // Grouper par sections principales (catégories)
    $mainSections = $serviceChecks->groupBy(function ($sc) {
        $category = $sc->service->category ?? null;
        return $category ? $category->title : 'Autres';
    });

    foreach ($mainSections as $mainSectionTitle => $sectionServices) {
        // === TITRE DE SECTION PRINCIPALE (gris foncé) ===
        $drawFilledRect(0, $y, $width, $sectionHeaderHeight, $sectionBgColor);
        // Bordures (rectangles fins pour simuler des lignes épaisses)
        $borderWidth = 2;
        // Ligne du haut
        $drawFilledRect(0, $y, $width, $borderWidth, '#000000');
        // Ligne du bas
        $drawFilledRect(0, $y + $sectionHeaderHeight - $borderWidth, $width, $borderWidth, '#000000');
        // Ligne de gauche
        $drawFilledRect(0, $y, $borderWidth, $sectionHeaderHeight, '#000000');
        // Ligne de droite
        $drawFilledRect($width - $borderWidth, $y, $borderWidth, $sectionHeaderHeight, '#000000');
        $img->text($mainSectionTitle, $padding, $y + $sectionHeaderHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(44);
            $font->color('#FFFFFF');
            $font->valign('middle');
        });
        $y += $sectionHeaderHeight;

        // === EN-TÊTE DE COLONNES (gris foncé) ===
        $headerY = $y;
        $drawFilledRect(0, $headerY, $width, $rowHeight, $headerBgColorDark);
        // Bordures
        $drawFilledRect(0, $headerY, $width, $cellBorderWidth, '#000000');
        $drawFilledRect(0, $headerY + $rowHeight - $cellBorderWidth, $width, $cellBorderWidth, '#000000');
        $drawFilledRect(0, $headerY, $cellBorderWidth, $rowHeight, '#000000');
        $drawFilledRect($width - $cellBorderWidth, $headerY, $cellBorderWidth, $rowHeight, '#000000');
        
        // 3 colonnes : Description | État | Observations
        $drawFilledRect($separator1X, $headerY, $cellBorderWidth, $rowHeight, '#000000');
        $drawFilledRect($separator2X, $headerY, $cellBorderWidth, $rowHeight, '#000000');
        
        $img->text('Description', $padding + $cellPadding, $headerY + $rowHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(34);
            $font->color('#FFFFFF');
            $font->align('left');
            $font->valign('middle');
        });
        $img->text('État', $separator1X + ($colEtatWidth / 2), $headerY + $rowHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(34);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });
        $img->text('Observations', $separator2X + ($colObservationsWidth / 2), $headerY + $rowHeight / 2, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(34);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });
        $y += $rowHeight;

        // Afficher les services
        $rowIndex = 0;
        foreach ($sectionServices as $serviceCheck) {
            $bgColor = ($rowIndex % 2 === 0) ? '#FFFFFF' : $rowAltColor;
            $rowY = $y;
            
            // Ligne de service
            $drawFilledRect(0, $rowY, $width, $rowHeight, $bgColor);
            // Bordures horizontales
            $drawFilledRect(0, $rowY, $width, $cellBorderWidth, '#000000');
            $drawFilledRect(0, $rowY + $rowHeight - $cellBorderWidth, $width, $cellBorderWidth, '#000000');
            // Bordures verticales
            $drawFilledRect(0, $rowY, $cellBorderWidth, $rowHeight, '#000000');
            $drawFilledRect($width - $cellBorderWidth, $rowY, $cellBorderWidth, $rowHeight, '#000000');
            
            // Déterminer les labels et couleurs
            $statusLabel = match ($serviceCheck->statut) {
                'success' => 'OK',
                'error'   => 'KO',
                'warning' => 'AVERTISSEMENT',
                'pending' => 'EN ATTENTE',
                'in_progress' => 'EN COURS',
                default   => strtoupper($serviceCheck->statut ?? 'INCONNU'),
            };

            $statusColor = match ($serviceCheck->statut) {
                'success' => $okColor,
                'error'   => $nokColor,
                'warning' => $warningColor,
                'pending' => $warningColor,
                'in_progress' => $warningColor,
                default   => '#999999',
            };
            
            // 3 colonnes : Description | État | Observations
            // Bordures verticales
            $drawFilledRect($separator1X, $rowY, $cellBorderWidth, $rowHeight, '#000000');
            $drawFilledRect($separator2X, $rowY, $cellBorderWidth, $rowHeight, '#000000');
            
            // Description (colonne 1) - titre du service
            $serviceText = $serviceCheck->service->title ?? 'N/A';
            $serviceTextX = $padding + $cellPadding;
            $img->text($serviceText, $serviceTextX, $rowY + $rowHeight / 2, function ($font) use ($fontPath) {
                if ($fontPath) $font->file($fontPath);
                $font->size(30);
                $font->color('#000000');
                $font->align('left');
                $font->valign('middle');
            });
            
            // État (colonne 2) - OK en vert, KO en rouge, etc.
            $etatCellX = $separator1X + $cellBorderWidth;
            $etatCellWidth = $colEtatWidth - ($cellBorderWidth * 2);
            $etatCellY = $rowY + $cellBorderWidth;
            $etatCellHeight = $rowHeight - ($cellBorderWidth * 2);
            $drawFilledRect($etatCellX, $etatCellY, $etatCellWidth, $etatCellHeight, $statusColor);
            $etatTextX = $separator1X + ($colEtatWidth / 2);
            $img->text($statusLabel, $etatTextX, $rowY + $rowHeight / 2, function ($font) use ($fontPath) {
                if ($fontPath) $font->file($fontPath);
                $font->size(32);
                $font->color('#FFFFFF');
                $font->align('center');
                $font->valign('middle');
            });
            
            // Observations (colonne 3) - observations/commentaires
            $observations = $serviceCheck->observations ?? $serviceCheck->notes ?? '';
            $observationsTextX = $separator2X + $cellPadding;
            $img->text($observations, $observationsTextX, $rowY + $rowHeight / 2, function ($font) use ($fontPath) {
                if ($fontPath) $font->file($fontPath);
                $font->size(28);
                $font->color('#000000');
                $font->align('left');
                $font->valign('middle');
            });

            $y += $rowHeight;
            $rowIndex++;
        }
        $y += 20; // Espace entre sections
    }

    // === FOOTER ===
    $footerY = $height - $footerHeight;
    $drawFilledRect(0, $footerY, $width, $footerHeight, $footerColor);
    
    // Ligne principale en blanc
    $footerTextMain = $template->footer_text ?? 'EXPLOITATION, Connecte Châlons : https://glpi.connecte-chalons.fr';
    $footerTextX = $padding + 20;
    $footerTextY = $footerY + 40;
    $img->text($footerTextMain, $footerTextX, $footerTextY, function ($font) use ($fontPath) {
        if ($fontPath) $font->file($fontPath);
        $font->size(32);
        $font->color('#FFFFFF');
        $font->align('left');
        $font->valign('middle');
    });
    
    // Ligne d'urgence en rouge (plus petite)
    $footerTextUrgent = '(en cas d\'urgence uniquement): support.chalons@bouyguestelecom-solution.fr';
    $footerTextUrgentY = $footerY + 85;
    $img->text($footerTextUrgent, $footerTextX, $footerTextUrgentY, function ($font) use ($fontPath) {
        if ($fontPath) $font->file($fontPath);
        $font->size(26);
        $font->color('#FF0000');
        $font->align('left');
        $font->valign('middle');
    });

    // Output
    $pngContent = (string) $img->encode('png');
    
    if ($forDownload) {
        $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.png";
        return response($pngContent)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }
    
    return $pngContent;
    
    } catch (\Throwable $e) {
        \Log::error('Erreur génération PNG: ' . $e->getMessage(), [
            'exception' => $e,
            'check_id' => $check->id ?? null,
            'client_id' => $client->id ?? null,
            'template_id' => $template->id ?? null,
        ]);
        
        // Si c'est pour un download, retourner une erreur
        if ($forDownload) {
            throw $e;
        }
        
        // Sinon, retourner une image d'erreur simple ou null
        // Pour l'email, on préfère retourner null et laisser l'email se générer sans PNG
        return null;
    }
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
        
        // Services vérifiés - Groupés par catégories parent
        if ($data['serviceChecks']->count() > 0) {
            $csv[] = ['Services vérifiés'];
            
            // Grouper par catégorie parent
            $groupedByParent = $data['serviceChecks']->groupBy(function ($serviceCheck) {
                $category = $serviceCheck->service->category ?? null;
                if ($category && $category->parent) {
                    return $category->parent->title;
                }
                return $category ? $category->title : 'Autres';
            });
            
            foreach ($groupedByParent as $parentTitle => $serviceChecks) {
                // En-tête de section pour la catégorie parent
                $csv[] = []; // Ligne vide
                $csv[] = [$parentTitle]; // Titre de la catégorie parent
                
                // Obtenir la catégorie parent pour cette section
                $firstServiceCheck = $serviceChecks->first();
                $parentCategory = $firstServiceCheck->service->category->parent ?? $firstServiceCheck->service->category ?? null;
                
                // Obtenir la configuration des colonnes pour cette catégorie
                $exportColumns = $this->getExportColumns($template, $client, $parentCategory);
                $headers = array_column($exportColumns, 'label');
                $csv[] = $headers;
                
                // Afficher les statistiques si configuré
                if ($parentCategory && $parentCategory->show_stats) {
                    $stats = $this->calculateCategoryStats($serviceChecks, $parentCategory);
                    if (!empty($stats)) {
                        $statsRow = [];
                        foreach ($stats as $stat) {
                            $statsRow[] = "{$stat['label']}: {$stat['value']}";
                        }
                        $csv[] = $statsRow;
                    }
                }
                
                foreach ($serviceChecks as $serviceCheck) {
                    $category = $serviceCheck->service->category ?? null;
                    $row = [];
                    
                    foreach ($exportColumns as $column) {
                        $row[] = $this->getColumnValue($serviceCheck, $column['field'], $category);
                    }
                    
                    $csv[] = $row;
                }
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
        
        // Services vérifiés - Groupés par catégories parent
        if ($data['serviceChecks']->count() > 0) {
            $html .= '<h3>Services vérifiés</h3>';
            
            // Obtenir la configuration des colonnes
            $exportColumns = $this->getExportColumns($template, $client);
            
            // Grouper par catégorie parent
            $groupedByParent = $data['serviceChecks']->groupBy(function ($serviceCheck) {
                $category = $serviceCheck->service->category ?? null;
                if ($category && $category->parent) {
                    return $category->parent->title;
                }
                return $category ? $category->title : 'Autres';
            });
            
            foreach ($groupedByParent as $parentTitle => $serviceChecks) {
                // Obtenir la catégorie parent pour cette section
                $firstServiceCheck = $serviceChecks->first();
                $parentCategory = $firstServiceCheck->service->category->parent ?? $firstServiceCheck->service->category ?? null;
                
                // Obtenir la configuration des colonnes pour cette catégorie
                $exportColumns = $this->getExportColumns($template, $client, $parentCategory);
                
                $html .= '<h4 style="background-color: #f5f5f5; padding: 10px; margin-top: 20px;">' . htmlspecialchars($parentTitle) . '</h4>';
                
                // Afficher les statistiques si configuré
                if ($parentCategory && $parentCategory->show_stats) {
                    $stats = $this->calculateCategoryStats($serviceChecks, $parentCategory);
                    if (!empty($stats)) {
                        $html .= '<div style="background-color: #e3f2fd; padding: 10px; margin-bottom: 10px; border-left: 4px solid #2196F3;">';
                        foreach ($stats as $stat) {
                            $html .= '<strong>' . htmlspecialchars($stat['label']) . ':</strong> ' . htmlspecialchars($stat['value']) . ' | ';
                        }
                        $html = rtrim($html, ' | ') . '</div>';
                    }
                }
                
                $html .= '<table class="services-table">
                    <thead>
                        <tr>';
                
                // En-têtes de colonnes selon la configuration
                foreach ($exportColumns as $column) {
                    $html .= '<th>' . htmlspecialchars($column['label']) . '</th>';
                }
                
                $html .= '</tr>
                    </thead>
                    <tbody>';
                
                foreach ($serviceChecks as $serviceCheck) {
                    $category = $serviceCheck->service->category ?? null;
                    $html .= '<tr>';
                    
                    foreach ($exportColumns as $column) {
                        $value = $this->getColumnValue($serviceCheck, $column['field'], $category);
                        $cellStyle = '';
                        $cellClass = '';
                        
                        // Styles spécifiques selon le type de colonne
                        if ($column['field'] === 'expiration_date' && $serviceCheck->expiration_date) {
                            $daysUntilExpiration = now()->diffInDays($serviceCheck->expiration_date, false);
                            if ($daysUntilExpiration < 0) {
                                $cellStyle = 'background-color: #ffebee; color: #c62828; font-weight: bold;';
                            } elseif ($daysUntilExpiration <= 30) {
                                $cellStyle = 'background-color: #fff3e0; color: #e65100;';
                            }
                        } elseif ($column['field'] === 'statut') {
                            $cellClass = $this->getServiceStatusClass($serviceCheck->statut ?? 'pending');
                        }
                        
                        $html .= '<td class="' . $cellClass . '" style="' . $cellStyle . '">' . htmlspecialchars($value) . '</td>';
                    }
                    
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
            }
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

    /**
     * Get export columns configuration for a template/client/category
     * Returns default columns if not configured
     * 
     * @param \App\Models\Template $template
     * @param \App\Models\Client $client
     * @param \App\Models\Category|null $category
     * @return array
     */
    private function getExportColumns($template, $client, $category = null)
    {
        // PRIORITÉ 1 : Si la catégorie a une configuration spécifique, l'utiliser
        if ($category && $category->export_columns && is_array($category->export_columns) && !empty($category->export_columns)) {
            return $category->export_columns;
        }

        // PRIORITÉ 2 : Si une configuration existe dans le template, l'utiliser
        if ($template->export_columns && is_array($template->export_columns) && !empty($template->export_columns)) {
            return $template->export_columns;
        }

        // PRIORITÉ 3 : Configuration spécifique pour Chalons (ancien format simple)
        if (stripos($client->label, 'chalons') !== false || stripos($client->label, 'châlons') !== false) {
            return [
                ['field' => 'description', 'label' => 'Description'],
                ['field' => 'statut', 'label' => 'Etat'],
            ];
        }

        // PRIORITÉ 4 : Configuration par défaut pour tous les clients (nouveau format)
        return [
            ['field' => 'description', 'label' => 'Description'],
            ['field' => 'category_full_path', 'label' => 'Catégorie complète'],
            ['field' => 'statut', 'label' => 'Etat'],
            ['field' => 'expiration_date', 'label' => 'Date d\'expiration'],
            ['field' => 'notes', 'label' => 'Notes'],
        ];
    }

    /**
     * Get the value for a specific column field
     */
    private function getColumnValue($serviceCheck, $field, $category = null)
    {
        switch ($field) {
            case 'description':
                return $serviceCheck->service->title ?? 'N/A';
            
            case 'category_full_path':
                if ($category) {
                    return $category->full_path;
                }
                $cat = $serviceCheck->service->category ?? null;
                return $cat ? $cat->full_path : 'N/A';
            
            case 'statut':
                $status = $serviceCheck->statut ?? 'pending';
                return $status === 'success' ? 'OK' : ($status === 'error' ? 'NOK' : strtoupper($status));
            
            case 'expiration_date':
                return $serviceCheck->expiration_date ? $serviceCheck->expiration_date->format('d/m/Y') : 'N/A';
            
            case 'notes':
                return $serviceCheck->notes ?? '';
            
            case 'observations':
                return $serviceCheck->observations ?? '';
            
            case 'intervenant':
                return $serviceCheck->intervenantUser->name ?? 'N/A';
            
            case 'created_at':
                return $serviceCheck->created_at->format('d/m/Y H:i');
            
            default:
                return 'N/A';
        }
    }

    /**
     * Calculate statistics for a category (e.g., Abonnements)
     */
    private function calculateCategoryStats($serviceChecks, $category)
    {
        if (!$category->show_stats) {
            return [];
        }

        $stats = [];
        
        // Exemple pour les abonnements : calculer consommé, total, disponibles
        // Cette logique peut être personnalisée selon le type de catégorie
        $statsConfig = $category->stats_config ?? [];
        
        // Par défaut, on peut calculer des stats basiques
        $total = $serviceChecks->count();
        $ok = $serviceChecks->where('statut', 'success')->count();
        $nok = $serviceChecks->where('statut', 'error')->count();
        $warning = $serviceChecks->where('statut', 'warning')->count();
        
        // Si la catégorie a une config de stats personnalisée, l'utiliser
        if (!empty($statsConfig)) {
            foreach ($statsConfig as $statKey => $statConfig) {
                $value = $this->calculateStatValue($serviceChecks, $statKey, $statConfig);
                if ($value !== null) {
                    $stats[] = [
                        'label' => $statConfig['label'] ?? ucfirst($statKey),
                        'value' => $value
                    ];
                }
            }
        } else {
            // Stats par défaut
            $stats[] = ['label' => 'Total', 'value' => $total];
            $stats[] = ['label' => 'OK', 'value' => $ok];
            if ($nok > 0) {
                $stats[] = ['label' => 'NOK', 'value' => $nok];
            }
            if ($warning > 0) {
                $stats[] = ['label' => 'Attention', 'value' => $warning];
            }
        }
        
        return $stats;
    }

    /**
     * Calculate a specific stat value
     */
    private function calculateStatValue($serviceChecks, $statKey, $statConfig)
    {
        switch ($statKey) {
            case 'total':
                return $serviceChecks->count();
            case 'ok':
                return $serviceChecks->where('statut', 'success')->count();
            case 'nok':
                return $serviceChecks->where('statut', 'error')->count();
            case 'warning':
                return $serviceChecks->where('statut', 'warning')->count();
            case 'consumed':
                // Pour les abonnements, peut être basé sur les notes ou observations
                return $serviceChecks->where('statut', 'success')->count();
            default:
                return null;
        }
    }

    /**
     * Format statistics for Excel display
     */
    private function formatStatsForExcel($stats)
    {
        $parts = [];
        foreach ($stats as $stat) {
            $parts[] = "{$stat['label']}: {$stat['value']}";
        }
        return implode(' | ', $parts);
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
        // Vérifier si on est en environnement local
        // Le SMTP ne fonctionne que sur le serveur de production
        $isLocal = app()->environment('local') || 
                   config('app.debug') === true ||
                   config('app.env') === 'local' ||
                   config('mail.default') === 'log' ||
                   config('mail.default') === 'array';
        
        if ($isLocal) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => "L'envoi d'email n'est pas disponible en environnement local. L'email sera envoyé uniquement sur le serveur de production."
                ], 422);
            }
            return back()->with('error', "L'envoi d'email n'est pas disponible en environnement local. L'email sera envoyé uniquement sur le serveur de production.");
        }

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
                return response()->json(['success' => false, 'message' => "Erreur lors de la génération du fichier joint. Vérifiez les logs pour plus de détails."], 422);
            }
            return back()->with('error', "Erreur lors de la génération du fichier joint. Vérifiez les logs pour plus de détails.");
        }

        // Prepare and send email
        $subject = "Bulletin de santé - {$client->label}";
        
        // Générer le HTML de l'email selon le type de template
        $emailHtml = $this->generateEmailHtml($check, $client, $attachment);
        
        try {
            // Déterminer l'expéditeur : priorité aux senders du client, sinon config globale
            $fromEmail = !empty($senders) ? $senders[0] : config('mail.from.address');
            $fromName = config('mail.from.name', 'Check du Matin');
            
            // Log pour déboguer
            \Log::info('Envoi email check', [
                'client' => $client->label,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'senders' => $senders,
                'receivers' => $receivers,
            ]);
            
            Mail::send([], [], function ($message) use ($receivers, $senders, $subject, $attachment, $emailHtml, $fromEmail, $fromName) {
                $message->to($receivers);
                
                // Définir l'expéditeur avec email et nom (doit être défini AVANT les autres méthodes)
                $message->from($fromEmail, $fromName);
                
                // Forcer l'expéditeur en utilisant aussi sender() pour certains serveurs SMTP
                $message->sender($fromEmail, $fromName);
                
                // Put other senders in CC if present
                if (count($senders) > 1) {
                    $message->cc(array_slice($senders, 1));
                }
                $message->subject($subject);
                $message->html($emailHtml);
                
                // Attacher le fichier seulement si les données sont valides
                if (!empty($attachment['data'])) {
                    $message->attachData($attachment['data'], $attachment['filename'], [
                        'mime' => $attachment['mime'] ?? 'application/octet-stream',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            \Log::error('Erreur envoi email: ' . $e->getMessage(), [
                'check_id' => $check->id,
                'client_id' => $client->id ?? null,
                'exception' => $e,
            ]);
            
            $errorMessage = "Échec de l'envoi de l'email";
            // Message plus explicite pour les erreurs de connexion SMTP
            $errorMsg = $e->getMessage();
            $isProduction = app()->environment('production');
            
            if (str_contains($errorMsg, 'Connection refused') || 
                str_contains($errorMsg, 'Unable to connect') || 
                str_contains($errorMsg, 'Connection could not be established')) {
                $errorMessage .= ": Le serveur SMTP n'est pas accessible. ";
                if ($isProduction) {
                    $errorMessage .= "Vérifiez que le serveur SMTP (relais.services.c-2-s.info:25) est accessible depuis le conteneur Docker et que le port 25 n'est pas bloqué par un firewall.";
                } else {
                    $errorMessage .= "En développement local, configurez MAIL_MAILER=log dans votre .env pour éviter cette erreur.";
                }
            } elseif (str_contains($errorMsg, 'SSL') || str_contains($errorMsg, 'STARTTLS') || str_contains($errorMsg, 'certificate')) {
                $errorMessage .= ": Erreur de connexion SMTP. Vérifiez la configuration SMTP (encryption, certificats) dans votre fichier .env.";
            } else {
                $errorMessage .= ": " . $errorMsg;
            }
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }

        // Enregistrer la date d'envoi de l'email
        $check->update(['email_sent_at' => now()]);

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
                try {
                    $data = [
                        'check' => $check,
                        'client' => $client,
                        'template' => $template,
                        'serviceChecks' => $check->serviceChecks,
                    ];
                    $pngData = $this->generatePngImage($data, false);
                    
                    // Si la génération a échoué, retourner null
                    if ($pngData === null || empty($pngData)) {
                        \Log::warning('Génération PNG retournée vide pour check ' . $check->id);
                        return null;
                    }
                    
                    $filename = "check_{$client->label}_{$check->date_time->format('Y-m-d_H-i')}.png";
                    return [
                        'data' => $pngData,
                        'filename' => $filename,
                        'mime' => 'image/png',
                    ];
                } catch (\Throwable $e) {
                    \Log::error('Erreur buildCheckAttachment PNG: ' . $e->getMessage(), [
                        'check_id' => $check->id,
                        'exception' => $e,
                    ]);
                    return null;
                }
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
        table { width: 100%; border-collapse: collapse; margin-top: 0; border-spacing: 0; }
        table th { background-color: #f5f5f5; padding: 12px 15px; text-align: left; border: 1px solid #ddd; font-weight: bold; vertical-align: middle; }
        table td { padding: 12px 15px; border: 1px solid #ddd; word-wrap: break-word; vertical-align: top; }
        /* Largeurs fixes en pourcentage pour toutes les colonnes : Description (50%), État (25%), Observations (25%) */
        table th:first-child, table td:first-child { width: 50%; text-align: left; }
        table th:nth-child(2), table td:nth-child(2) { width: 25%; text-align: center; }
        table th:nth-child(3), table td:nth-child(3) { width: 25%; text-align: left; }
        .status-ok { color: #00B050; font-weight: bold; }
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
                            <th style="text-align: left;">Description</th>
                            <th style="text-align: center;">État</th>
                            <th style="text-align: left;">Observations</th>
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
                    'pending' => 'status-warning',
                    'in_progress' => 'status-warning',
                    default => ''
                };
                
                $statusLabel = match($sc->statut) {
                    'success' => 'OK',
                    'error' => 'NOK',
                    'warning' => 'AVERTISSEMENT',
                    'pending' => 'EN ATTENTE',
                    'in_progress' => 'EN COURS',
                    default => strtoupper($sc->statut ?? 'INCONNU')
                };
                
                $observations = $sc->observations ?? $sc->notes ?? '';
                
                // Colonne État (alignée à droite)
                $statusDisplay = '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
                
                // Colonne Observations (alignée à gauche)
                $observationsDisplay = !empty($observations) ? htmlspecialchars($observations) : '';
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($sc->service->title ?? 'N/A') . '</td>
                    <td style="text-align: center;">' . $statusDisplay . '</td>
                    <td>' . $observationsDisplay . '</td>
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
