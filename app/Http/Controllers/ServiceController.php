<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use ManagesUserPermissions;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Service::with(['category.client', 'creator']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('category', function($categoryQuery) use ($search) {
                      $categoryQuery->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('category.client', function($clientQuery) use ($search) {
                      $clientQuery->where('label', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par client
        if ($request->filled('client_id')) {
            $query->whereHas('category', function($categoryQuery) use ($request) {
                $categoryQuery->where('client_id', $request->client_id);
            });
        }

        // Filtre par catégorie
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Tri (plus récent en haut par défaut)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Filtrer selon les permissions de l'utilisateur
        $user = auth()->user();
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereHas('category.client', function($q) use ($clientIds) {
                $q->whereIn('id', $clientIds);
            });
        }

        $perPage = $request->get('per_page', 10);
        $services = $query->paginate($perPage)->withQueryString();
        
        // Filtrer aussi les listes de clients et catégories selon les permissions
        if ($user->isGestionnaire()) {
            $clients = $user->clients()->orderBy('label')->get();
            $categories = Category::whereIn('client_id', $clientIds)->with('client')->orderBy('title')->get();
        } else {
            $clients = \App\Models\Client::orderBy('label')->get();
            $categories = \App\Models\Category::with('client')->orderBy('title')->get();
        }

        return view('services.index', compact('services', 'clients', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id'
        ]);

        $service = Service::create([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'created_by' => Auth::id()
        ]);

        return response()->json($service);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        return response()->json($service);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $service->update([
            'title' => $request->title
        ]);

        return response()->json($service);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['success' => true]);
    }
}
