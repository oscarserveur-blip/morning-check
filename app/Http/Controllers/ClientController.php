<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\Template;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    use ManagesUserPermissions;
    public function index(Request $request)
    {
        $query = Client::with('template');
        $this->filterClientsByUserPermissions($query);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('label', 'like', "%{$search}%");
        }

        // Filtre par template
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        // Tri (plus récent en haut par défaut)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $clients = $query->paginate($perPage)->withQueryString();
        $templates = Template::orderBy('name')->get();

        return view('clients.index', compact('clients', 'templates'));
    }

    public function show(Client $client, Request $request)
    {
        $this->authorizeClientAccess($client);
        
        // Charger les relations nécessaires
        $client->load(['template', 'categories.services']);
        $users = User::all();

        // Gérer la pagination et la recherche pour les checks
        $checksQuery = $client->checks()->with(['creator', 'serviceChecks']);

        // Recherche dans les checks
        if ($request->filled('check_search')) {
            $search = $request->check_search;
            $checksQuery->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('creator', function($creatorQuery) use ($search) {
                      $creatorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut des checks
        if ($request->filled('check_status')) {
            $checksQuery->where('statut', $request->check_status);
        }

        // Filtre par date des checks
        if ($request->filled('check_date_from')) {
            $checksQuery->whereDate('date_time', '>=', $request->check_date_from);
        }
        if ($request->filled('check_date_to')) {
            $checksQuery->whereDate('date_time', '<=', $request->check_date_to);
        }

        // Tri des checks (plus récent en haut par défaut)
        $checkSortBy = $request->get('check_sort_by', 'date_time');
        $checkSortOrder = $request->get('check_sort_order', 'desc');
        $checksQuery->orderBy($checkSortBy, $checkSortOrder);

        $perPage = $request->get('per_page', 10);
        $checks = $checksQuery->paginate($perPage)->withQueryString();

        return view('clients.show', compact('client', 'users', 'checks'));
    }

    public function create()
    {
        $templates = Template::all();
        return view('clients.form', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'template_id' => 'required|exists:templates,id',
            'check_time' => 'required|string'
        ]);

        // Formater l'heure au format H:i
        if ($request->has('check_time')) {
            $time = $request->input('check_time');
            // Convertir différents formats d'heure en H:i
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
                $validated['check_time'] = sprintf('%02d:%02d', $matches[1], $matches[2]);
            } else {
                return redirect()->back()->withErrors(['check_time' => 'Le format de l\'heure doit être HH:MM'])->withInput();
            }
        }

        // Gestion de l'upload du logo
        if ($request->hasFile('logo')) {
            try {
                $file = $request->file('logo');
                
                // Vérifier que le fichier est valide
                if (!$file->isValid()) {
                    return redirect()->back()->withErrors(['logo' => 'Le fichier uploadé n\'est pas valide'])->withInput();
                }

                // Générer un nom unique pour le fichier
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Stocker le fichier
                $path = $file->storeAs('logos', $filename, 'public');
                
                if ($path) {
                    $validated['logo'] = $path;
                } else {
                    return redirect()->back()->withErrors(['logo' => 'Erreur lors du stockage du fichier'])->withInput();
                }
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['logo' => 'Erreur lors de l\'upload: ' . $e->getMessage()])->withInput();
            }
        }

        Client::create($validated);

        return redirect()->route('clients.index', absolute: false)
            ->with('success', 'Client créé avec succès.');
    }

    public function edit(Client $client)
    {
        $templates = Template::all();
        if (request()->ajax()) {
            return view('clients.partials.form', compact('client', 'templates'));
        }
        return view('clients.form', compact('client', 'templates'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'template_id' => 'required|exists:templates,id',
            'check_time' => 'required|string'
        ]);

        // Formater l'heure au format H:i
        if ($request->has('check_time')) {
            $time = $request->input('check_time');
            // Convertir différents formats d'heure en H:i
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
                $validated['check_time'] = sprintf('%02d:%02d', $matches[1], $matches[2]);
            } else {
                return redirect()->back()->withErrors(['check_time' => 'Le format de l\'heure doit être HH:MM'])->withInput();
            }
        }

        // Gestion de l'upload du logo
        if ($request->hasFile('logo')) {
            try {
                $file = $request->file('logo');
                
                // Vérifier que le fichier est valide
                if (!$file->isValid()) {
                    return redirect()->back()->withErrors(['logo' => 'Le fichier uploadé n\'est pas valide'])->withInput();
                }

                // Supprimer l'ancien logo s'il existe
                if ($client->logo && Storage::disk('public')->exists($client->logo)) {
                    Storage::disk('public')->delete($client->logo);
                }

                // Générer un nom unique pour le fichier
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Stocker le fichier
                $path = $file->storeAs('logos', $filename, 'public');
                
                if ($path) {
                    $validated['logo'] = $path;
                } else {
                    return redirect()->back()->withErrors(['logo' => 'Erreur lors du stockage du fichier'])->withInput();
                }
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['logo' => 'Erreur lors de l\'upload: ' . $e->getMessage()])->withInput();
            }
        }

        $client->update($validated);

        // Si la requête attend du JSON (AJAX), on renvoie du JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès.'
            ]);
        }

        // Sinon, on fait la redirection classique
        return redirect()->route('clients.index', absolute: false)
            ->with('success', 'Client mis à jour avec succès.');
    }

    public function destroy(Client $client)
    {
        // Vérifier les autorisations
        $this->authorizeClientAccess($client);
        
        // Utiliser une transaction pour garantir l'intégrité des données
        DB::transaction(function () use ($client) {
            // 1. Supprimer les service_checks liés aux checks du client
            foreach ($client->checks as $check) {
                $check->serviceChecks()->delete();
            }
            
            // 2. Supprimer les checks
            $client->checks()->delete();
            
            // 3. Supprimer les services et leurs service_checks
            foreach ($client->categories as $category) {
                foreach ($category->services as $service) {
                    // Supprimer les service_checks liés à ce service
                    DB::table('service_checks')->where('service_id', $service->id)->delete();
                    // Supprimer le service
                    $service->delete();
                }
            }
            
            // 4. Supprimer les catégories
            $client->categories()->delete();
            
            // 5. Supprimer le logo
            if ($client->logo) {
                Storage::disk('public')->delete($client->logo);
            }
            
            // 6. Supprimer le client
            $client->delete();
        });

        return redirect('/clients')
            ->with('success', 'Client supprimé avec succès.');
    }

    public function checksList(Request $request, Client $client)
    {
        $checksQuery = $client->checks()->with(['creator', 'serviceChecks']);
        // (Filtres éventuels à reprendre si besoin)
        $checksQuery->orderBy('date_time', 'desc');
        $checks = $checksQuery->paginate(10);
        return view('clients.partials.tabs.checks-list', compact('checks', 'client'))->render();
    }

    /**
     * Create an automatic check for today.
     */
    public function autoCheck(Client $client)
    {
        // Vérifier s'il existe déjà un check pour aujourd'hui
        $existing = $client->checks()
            ->whereDate('date_time', now()->toDateString())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Un check existe déjà pour aujourd\'hui.',
                'check' => $existing
            ]);
        }

        // Créer le check
        $check = $client->checks()->create([
            'date_time' => now(),
            'statut' => 'pending',
            'created_by' => auth()->id()
        ]);

        // Créer les service checks pour tous les services du client
        foreach ($client->services as $service) {
            $check->serviceChecks()->create([
                'service_id' => $service->id,
                'statut' => 'pending'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check créé avec succès.',
            'check' => $check->load('serviceChecks')
        ]);
    }

    public function duplicate($id)
    {
        $client = \App\Models\Client::with(['categories.services'])->findOrFail($id);
        // Dupliquer le client
        $newClient = $client->replicate();
        $newClient->label = $client->label . ' (copie)';
        $newClient->push();
        // Dupliquer les catégories et services
        foreach ($client->categories as $category) {
            $newCategory = $category->replicate();
            $newCategory->client_id = $newClient->id;
            $newCategory->push();
            foreach ($category->services as $service) {
                $newService = $service->replicate();
                $newService->category_id = $newCategory->id;
                $newService->push();
            }
        }
        return redirect()->route('clients.edit', ['client' => $newClient->id], absolute: false)
            ->with('success', 'Client dupliqué avec ses catégories et services.');
    }

    /**
     * Retourner les catégories pour une liste d'IDs clients (JSON)
     */
    public function getCategories(Request $request)
    {
        $ids = $request->query('ids', []);
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return response()->json(['categories' => []]);
        }

        // Restreindre aux clients accessibles par l'utilisateur (gestionnaire)
        $user = auth()->user();
        if ($user && method_exists($user, 'isGestionnaire') && $user->isGestionnaire()) {
            $allowed = $user->clients()->pluck('clients.id')->toArray();
            $ids = array_values(array_intersect($ids, $allowed));
            if (empty($ids)) {
                return response()->json(['categories' => []]);
            }
        }

        $categories = \App\Models\Category::whereIn('client_id', $ids)
            ->orderBy('client_id')
            ->orderBy('title')
            ->get(['id', 'title', 'client_id']);

        return response()->json(['categories' => $categories]);
    }
}
