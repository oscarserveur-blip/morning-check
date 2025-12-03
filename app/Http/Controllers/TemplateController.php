<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    use ManagesUserPermissions;

    public function __construct()
    {
        // Pas de restriction globale - chaque utilisateur gère ses templates
    }

    public function index()
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            // Les gestionnaires voient les templates de leurs clients
            $clientIds = $user->clients->pluck('id');
            $templates = Template::whereHas('clients', function($query) use ($clientIds) {
                $query->whereIn('clients.id', $clientIds);
            })->get();
        } else {
            // Les administrateurs voient tous les templates
            $templates = Template::all();
        }
        
        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            // Les gestionnaires ne peuvent créer des templates que pour leurs clients
            $clients = $user->clients;
        } else {
            // Les administrateurs peuvent créer des templates pour tous les clients
            $clients = \App\Models\Client::all();
        }
        
        return view('templates.form', compact('clients'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:excel,pdf,png',
            'header_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'header_title' => 'nullable|string|max:255',
            'header_color' => 'nullable|string|max:32',
            'section_config' => 'nullable|json',
            'footer_text' => 'nullable|string|max:255',
            'footer_color' => 'nullable|string|max:32',
            'config' => 'nullable|json',
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        // Vérifier que l'utilisateur a accès aux clients sélectionnés
        if ($user->isGestionnaire()) {
            $userClientIds = $user->clients->pluck('id');
            $requestClientIds = $request->input('client_ids', []);
            
            if (!empty(array_diff($requestClientIds, $userClientIds->toArray()))) {
                abort(403, 'Vous ne pouvez créer des templates que pour vos clients assignés.');
            }
        }

        if ($request->hasFile('header_logo')) {
            try {
                $file = $request->file('header_logo');
                
                if (!$file->isValid()) {
                    return redirect()->back()->withErrors(['header_logo' => 'Le fichier uploadé n\'est pas valide'])->withInput();
                }

                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                $path = $file->storeAs('template_logos', $filename, 'public');
                
                if ($path) {
                    $validated['header_logo'] = $path;
                } else {
                    return redirect()->back()->withErrors(['header_logo' => 'Erreur lors du stockage du fichier'])->withInput();
                }
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['header_logo' => 'Erreur lors de l\'upload: ' . $e->getMessage()])->withInput();
            }
        }

        $template = Template::create($validated);

        // Gérer la configuration simplifiée
        $simpleConfig = $this->handleSimpleConfiguration($request);
        if (!empty($simpleConfig)) {
            $template->update($simpleConfig);
        }

        // Associer le template aux clients sélectionnés
        $clientIds = $request->input('client_ids', []);
        $template->clients()->attach($clientIds);

        return redirect()->route('templates.index')
            ->with('success', 'Template créé avec succès.');
    }

    public function edit(Template $template)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur a accès à ce template
        if ($user->isGestionnaire()) {
            $userClientIds = $user->clients->pluck('id');
            $templateClientIds = $template->clients->pluck('id');
            
            if (empty(array_intersect($userClientIds->toArray(), $templateClientIds->toArray()))) {
                abort(403, 'Vous n\'avez pas accès à ce template.');
            }
        }
        
        // Filtrer les clients disponibles selon le rôle
        if ($user->isGestionnaire()) {
            $clients = $user->clients;
        } else {
            $clients = \App\Models\Client::all();
        }
        
        return view('templates.form', compact('template', 'clients'));
    }

    public function update(Request $request, Template $template)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur a accès à ce template
        if ($user->isGestionnaire()) {
            $userClientIds = $user->clients->pluck('id');
            $templateClientIds = $template->clients->pluck('id');
            
            if (empty(array_intersect($userClientIds->toArray(), $templateClientIds->toArray()))) {
                abort(403, 'Vous n\'avez pas accès à ce template.');
            }
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:excel,pdf,png',
            'header_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'header_title' => 'nullable|string|max:255',
            'header_color' => 'nullable|string|max:32',
            'section_config' => 'nullable|json',
            'footer_text' => 'nullable|string|max:255',
            'footer_color' => 'nullable|string|max:32',
            'config' => 'nullable|json',
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        // Vérifier que l'utilisateur a accès aux clients sélectionnés
        if ($user->isGestionnaire()) {
            $userClientIds = $user->clients->pluck('id');
            $requestClientIds = $request->input('client_ids', []);
            
            if (!empty(array_diff($requestClientIds, $userClientIds->toArray()))) {
                abort(403, 'Vous ne pouvez modifier des templates que pour vos clients assignés.');
            }
        }

        if ($request->hasFile('header_logo')) {
            try {
                $file = $request->file('header_logo');
                
                if (!$file->isValid()) {
                    return redirect()->back()->withErrors(['header_logo' => 'Le fichier uploadé n\'est pas valide'])->withInput();
                }

                if ($template->header_logo && Storage::disk('public')->exists($template->header_logo)) {
                    Storage::disk('public')->delete($template->header_logo);
                }

                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                $path = $file->storeAs('template_logos', $filename, 'public');
                
                if ($path) {
                    $validated['header_logo'] = $path;
                } else {
                    return redirect()->back()->withErrors(['header_logo' => 'Erreur lors du stockage du fichier'])->withInput();
                }
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['header_logo' => 'Erreur lors de l\'upload: ' . $e->getMessage()])->withInput();
            }
        }

        $template->update($validated);

        // Gérer la configuration simplifiée
        $simpleConfig = $this->handleSimpleConfiguration($request, $template);
        if (!empty($simpleConfig)) {
            $template->update($simpleConfig);
        }

        // Mettre à jour l'association des clients
        $clientIds = $request->input('client_ids', []);
        $template->clients()->sync($clientIds);

        return redirect()->route('templates.index')
            ->with('success', 'Template mis à jour avec succès.');
    }

    public function destroy(Template $template)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur a accès à ce template
        if ($user->isGestionnaire()) {
            $userClientIds = $user->clients->pluck('id');
            $templateClientIds = $template->clients->pluck('id');
            
            if (empty(array_intersect($userClientIds->toArray(), $templateClientIds->toArray()))) {
                abort(403, 'Vous n\'avez pas accès à ce template.');
            }
        }
        
        if ($template->header_logo) {
            Storage::disk('public')->delete($template->header_logo);
        }
        $template->delete();
        return redirect()->route('templates.index')
            ->with('success', 'Template supprimé avec succès.');
    }

    public function duplicate($id)
    {
        $template = \App\Models\Template::findOrFail($id);
        $user = auth()->user();
        
        // Vérifier que l'utilisateur a accès à ce template
        if ($user->isGestionnaire()) {
            $userClientIds = $user->clients->pluck('id');
            $templateClientIds = $template->clients->pluck('id');
            
            if (empty(array_intersect($userClientIds->toArray(), $templateClientIds->toArray()))) {
                abort(403, 'Vous n\'avez pas accès à ce template.');
            }
        }
        
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (copie)';
        $newTemplate->save();
        
        // Copier les associations de clients
        $newTemplate->clients()->attach($template->clients->pluck('id'));
        
        return redirect()->route('templates.edit', $newTemplate->id)
            ->with('success', 'Template dupliqué avec succès. Modifiez-le selon vos besoins.');
    }

    /**
     * Gérer la configuration simplifiée des templates
     */
    private function handleSimpleConfiguration($request, $template = null)
{
    // Priorité: si le JSON est fourni depuis le formulaire, l'utiliser tel quel
    if ($request->filled('section_config') || $request->filled('config')) {
        $sectionConfigRaw = $request->input('section_config');
        $configRaw = $request->input('config');
        
        // Valider que ce sont des JSON valides; sinon fallback
        $sectionConfigOk = true;
        $configOk = true;
        try { 
            if ($sectionConfigRaw !== null) {
                $sectionData = json_decode($sectionConfigRaw, true, 512, JSON_THROW_ON_ERROR);
                // Si pas de sections, initialiser le tableau
                if (!isset($sectionData['sections'])) {
                    $sectionData['sections'] = [];
                }
                $sectionConfigRaw = json_encode($sectionData);
            } 
        } catch (\Throwable $e) { 
            $sectionConfigOk = false; 
        }
        try { 
            if ($configRaw !== null) {
                $configData = json_decode($configRaw, true, 512, JSON_THROW_ON_ERROR);
                $configRaw = json_encode($configData);
            } 
        } catch (\Throwable $e) { 
            $configOk = false; 
        }

        $result = [];
        if ($sectionConfigOk && $sectionConfigRaw !== null) { 
            $result['section_config'] = $sectionConfigRaw; 
        }
        if ($configOk && $configRaw !== null) { 
            $result['config'] = $configRaw; 
        }
        if (!empty($result)) { 
            return $result; 
        }
    }

    // Fallback: si les champs simplifiés sont présents, générer la configuration JSON
    if ($request->has('sections')) {
        $sections = [];
        $selectedSections = $request->input('sections', []);
        
        foreach ($selectedSections as $index => $section) {
            $sectionData = [];
            
            if (is_string($section) && str_starts_with($section, 'cat:')) {
                $categoryId = (int) substr($section, 4);
                if ($categoryId > 0) {
                    $sectionData = [
                        'id' => $categoryId,
                        'type' => 'category',
                        'order' => $index,
                        'color' => $request->input("section_color.{$categoryId}", '#666666')
                    ];
                }
            } elseif ($section === 'custom') {
                $name = $request->input('custom_section_name');
                $description = $request->input('custom_section_description');
                if ($name) {
                    $sectionData = [
                        'name' => $name,
                        'description' => $description,
                        'type' => 'custom',
                        'order' => $index,
                        'color' => $request->input('custom_section_color', '#666666')
                    ];
                }
            } else {
                $sectionData = [
                    'name' => $section,
                    'type' => 'predefined',
                    'order' => $index,
                    'color' => $request->input("section_color.{$section}", '#666666')
                ];
            }

            if (!empty($sectionData)) {
                $sections[] = $sectionData;
            }
        }
        
        $sectionConfig = ['sections' => $sections];
        $config = [
            'status_style' => $request->input('status_style', 'simple'),
            'date_format' => $request->input('date_format', 'french'),
            'show_timestamp' => $request->boolean('show_timestamp', true),
            'show_contact_info' => $request->boolean('show_contact_info', true),
            'ok_color' => $request->input('ok_color', '00B050'),
            'nok_color' => $request->input('nok_color', 'FF0000'),
            'font' => [
                'family' => $request->input('font_family', 'Arial'),
                'size' => $request->input('font_size', 12)
            ],
            'margins' => [
                'top' => $request->input('margin_top', 0),
                'bottom' => $request->input('margin_bottom', 0),
                'left' => $request->input('margin_left', 0),
                'right' => $request->input('margin_right', 0)
            ]
        ];
        
        return [
            'section_config' => json_encode($sectionConfig),
            'config' => json_encode($config)
        ];
    }
    
    return [];
}

    public function exportExample($id)
    {
        $template = \App\Models\Template::findOrFail($id);
        // Génère un fichier Excel d'exemple avec des données fictives
        // À adapter selon ta logique d'export réelle
        return back()->with('success', "Export d'exemple à implémenter.");
    }
} 