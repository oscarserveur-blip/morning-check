<?php

namespace App\Http\Controllers;

use App\Models\RappelDestinataire;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;

class RappelDestinataireController extends Controller
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
            $destinataires = RappelDestinataire::whereIn('client_id', $clientIds)->with('client')->get();
        } else {
            $destinataires = RappelDestinataire::with('client')->get();
        }
        
        return view('rappel-destinataires.index', compact('destinataires'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('rappel-destinataires.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:rappel_destinataires',
            'type' => 'required|in:sender,copie,receiver',
            'client_id' => 'required|exists:clients,id'
        ]);

        $destinataire = RappelDestinataire::create([
            'email' => $validated['email'],
            'type' => $validated['type'],
            'client_id' => $validated['client_id']
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Destinataire créé avec succès',
                'data' => $destinataire
            ]);
        }

        return redirect()->route('clients.show', ['client' => $validated['client_id'], 'tab' => 'destinataires'])
            ->with('success', 'Destinataire créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(RappelDestinataire $rappelDestinataire)
    {
        if (request()->wantsJson()) {
            return response()->json($rappelDestinataire);
        }
        return view('rappel-destinataires.show', compact('rappelDestinataire'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RappelDestinataire $rappelDestinataire)
    {
        return view('rappel-destinataires.edit', compact('rappelDestinataire'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RappelDestinataire $rappelDestinataire)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:rappel_destinataires,email,' . $rappelDestinataire->id,
            'type' => 'required|in:sender,copie,receiver'
        ]);

        $rappelDestinataire->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Destinataire mis à jour avec succès',
                'data' => $rappelDestinataire
            ]);
        }

        return redirect()->route('clients.show', ['client' => $rappelDestinataire->client_id, 'tab' => 'destinataires'])
            ->with('success', 'Destinataire mis à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RappelDestinataire $rappelDestinataire)
    {
        $clientId = $rappelDestinataire->client_id;
        $rappelDestinataire->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Destinataire supprimé avec succès'
            ]);
        }

        return redirect()->route('clients.show', ['client' => $clientId, 'tab' => 'destinataires'])
            ->with('success', 'Destinataire supprimé avec succès');
    }
}
