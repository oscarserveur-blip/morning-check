<?php

namespace App\Http\Controllers;

use App\Models\Mailing;
use App\Http\Traits\ManagesUserPermissions;
use Illuminate\Http\Request;

class MailingController extends Controller
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
            $mailings = Mailing::whereIn('client_id', $clientIds)->with('client')->get();
        } else {
            $mailings = Mailing::with('client')->get();
        }
        
        return view('mailings.index', compact('mailings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mailings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:mailings',
            'type' => 'required|in:sender,copie,receiver',
            'client_id' => 'required|exists:clients,id'
        ]);

        $mailing = Mailing::create([
            'email' => $validated['email'],
            'type' => $validated['type'],
            'client_id' => $validated['client_id'],
            'created_by' => auth()->id()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mailing créé avec succès',
                'data' => $mailing
            ]);
        }

        return redirect()->route('clients.show', ['client' => $validated['client_id'], 'tab' => 'mailings'])
            ->with('success', 'Mailing créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mailing $mailing)
    {
        return view('mailings.show', compact('mailing'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mailing $mailing)
    {
        return view('mailings.edit', compact('mailing'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mailing $mailing)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:mailings,email,' . $mailing->id,
            'type' => 'required|in:sender,copie,receiver'
        ]);

        $mailing->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mailing mis à jour avec succès',
                'data' => $mailing
            ]);
        }

        return redirect()->route('clients.show', ['client' => $mailing->client_id, 'tab' => 'mailings'])
            ->with('success', 'Mailing mis à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mailing $mailing)
    {
        $clientId = $mailing->client_id;
        $mailing->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mailing supprimé avec succès'
            ]);
        }

        return redirect()->route('clients.show', ['client' => $clientId, 'tab' => 'mailings'])
            ->with('success', 'Mailing supprimé avec succès');
    }
}
