<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use ManagesUserPermissions;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $categories = Category::whereIn('client_id', $clientIds)->with('parent')->get();
        } else {
            $categories = Category::with('parent')->get();
        }
        
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!request()->has('client_id')) {
            return redirect()->route('clients.index', absolute: false)
                ->with('error', 'Un client doit être sélectionné pour créer une catégorie.');
        }

        $parentCategories = Category::whereNull('category_pk')->get();
        return view('categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_pk' => 'nullable|exists:categories,id',
            'client_id' => 'required|exists:clients,id',
        ]);

        Category::create([
            'title' => $request->title,
            'category_pk' => $request->category_pk,
            'client_id' => $request->client_id,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('clients.show', ['client' => $request->client_id, 'tab' => 'categories'], absolute: false)
            ->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $this->authorizeClientAccess($category->client);
        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $parentCategories = Category::where('client_id', $category->client_id)
            ->where('id', '!=', $category->id)
            ->whereNotIn('id', $this->getChildCategoryIds($category))
            ->get();
        return view('categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_pk' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value == $category->id) {
                        $fail('Une catégorie ne peut pas être son propre parent.');
                    }
                    if (in_array($value, $this->getChildCategoryIds($category))) {
                        $fail('Une catégorie ne peut pas être le parent d\'une de ses sous-catégories.');
                    }
                },
            ],
            'category_export_columns_enabled' => 'nullable|array',
            'category_export_columns_labels' => 'nullable|array',
            'show_stats' => 'nullable|boolean',
        ]);

        // Traiter les colonnes d'export
        $exportColumns = null;
        if ($request->has('category_export_columns_enabled') && is_array($request->category_export_columns_enabled)) {
            $enabledColumns = $request->category_export_columns_enabled;
            $columnLabels = $request->input('category_export_columns_labels', []);
            $columnOrder = $request->input('category_export_columns_order', []);
            
            $exportColumns = [];
            $processedFields = [];
            
            // Traiter dans l'ordre spécifié
            if (!empty($columnOrder)) {
                foreach ($columnOrder as $field) {
                    if (in_array($field, $enabledColumns) && !in_array($field, $processedFields)) {
                        $exportColumns[] = [
                            'field' => $field,
                            'label' => $columnLabels[$field] ?? $this->getDefaultColumnLabel($field)
                        ];
                        $processedFields[] = $field;
                    }
                }
            }
            
            // Ajouter les autres colonnes cochées
            foreach ($enabledColumns as $field) {
                if (!in_array($field, $processedFields)) {
                    $exportColumns[] = [
                        'field' => $field,
                        'label' => $columnLabels[$field] ?? $this->getDefaultColumnLabel($field)
                    ];
                }
            }
        }

        $category->update([
            'title' => $request->title,
            'category_pk' => $request->category_pk,
            'export_columns' => $exportColumns,
            'show_stats' => $request->has('show_stats') && $request->show_stats == '1',
        ]);

        return redirect()->route('clients.show', ['client' => $category->client_id, 'tab' => 'categories'])
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Get default label for a column field
     */
    private function getDefaultColumnLabel($field)
    {
        $labels = [
            'description' => 'Description',
            'category_full_path' => 'Catégorie complète',
            'statut' => 'État',
            'expiration_date' => 'Date d\'expiration',
            'notes' => 'Notes',
            'observations' => 'Observations',
            'intervenant' => 'Intervenant',
            'created_at' => 'Date de vérification',
        ];
        
        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

        /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $this->authorizeClientAccess($category->client);
        
        $clientId = $category->client_id;
        
        // Vérifier que le client existe toujours
        $client = \App\Models\Client::find($clientId);
        if (!$client) {
            return redirect()->route('clients.index')
                ->with('error', 'Client non trouvé.');
        }
        
        // Vérifier s'il y a des sous-catégories
        $childCategories = Category::where('category_pk', $category->id)->count();
        if ($childCategories > 0) {
            return redirect()->route('clients.show', ['client' => $clientId, 'tab' => 'categories'])
                ->with('error', 'Impossible de supprimer cette catégorie car elle contient des sous-catégories.');
        }
        
        // Compter les services qui seront supprimés
        $servicesCount = $category->services()->count();
        
        // SUPPRESSION SÉCURISÉE : Supprimer manuellement les services d'abord
        foreach ($category->services as $service) {
            // Supprimer les service_checks d'abord
            \DB::table('service_checks')->where('service_id', $service->id)->delete();
            // Supprimer le service
            $service->delete();
        }
        
        // Supprimer la catégorie
        $category->delete();
        
        // Vérifier que le client existe toujours après la suppression
        $clientAfter = \App\Models\Client::find($clientId);
        if (!$clientAfter) {
            return redirect()->route('clients.index')
                ->with('error', 'Erreur : Le client a été supprimé par erreur. Veuillez contacter l\'administrateur.');
        }
        
        // Message de confirmation
        $message = 'Catégorie supprimée avec succès.';
        if ($servicesCount > 0) {
            $message .= " {$servicesCount} service(s) rattaché(s) ont également été supprimé(s).";
        }
        
        return redirect()->route('clients.show', ['client' => $clientId, 'tab' => 'categories'])
            ->with('success', $message);
    }

    public function getServices(Category $category)
    {
        return response()->json($category->services);
    }

    /**
     * Get all child category IDs recursively.
     */
    private function getChildCategoryIds(Category $category)
    {
        $ids = [];
        $children = Category::where('category_pk', $category->id)->get();
        
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getChildCategoryIds($child));
        }
        
        return $ids;
    }
}
