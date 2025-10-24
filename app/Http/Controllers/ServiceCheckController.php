<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\ServiceCheck;
use Illuminate\Http\Request;

class ServiceCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceCheck $serviceCheck)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceCheck $serviceCheck)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceCheck $serviceCheck)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceCheck $serviceCheck)
    {
        //
    }

    public function getCheckServices($checkId)
    {
        $serviceChecks = ServiceCheck::with(['service.category', 'intervenant'])
            ->where('check_id', $checkId)
            ->get()
            ->map(function($sc) {
                $arr = $sc->toArray();
                $arr['intervenant'] = $sc->intervenant ? (string)$sc->intervenant : '';
                return $arr;
            })
            ->groupBy(fn($sc) => $sc['service']['category']['title'] ?? '');

        \Log::info('getCheckServices - Check ID: ' . $checkId);
        \Log::info('getCheckServices - Données récupérées:', $serviceChecks->toArray());

        return response()->json($serviceChecks);
    }

    public function updateStatus(Request $request, ServiceCheck $serviceCheck)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,success,warning,error'
        ]);

        $serviceCheck->update([
            'statut' => $request->status
        ]);

        // Recalculer le statut du check principal
        $this->updateCheckStatut($serviceCheck->check);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès'
        ]);
    }

    public function updateComment(Request $request, ServiceCheck $serviceCheck)
    {
        $request->validate([
            'comment' => 'nullable|string'
        ]);

        $serviceCheck->update([
            'commentaire' => $request->comment
        ]);

        // Recalculer le statut du check principal
        $this->updateCheckStatut($serviceCheck->check);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire mis à jour avec succès'
        ]);
    }

    public function updateIntervenant(Request $request, ServiceCheck $serviceCheck)
    {
        $request->validate([
            'intervenant_id' => 'nullable|exists:users,id'
        ]);

        $serviceCheck->update([
            'intervenant' => $request->intervenant_id
        ]);

        // Recalculer le statut du check principal
        $this->updateCheckStatut($serviceCheck->check);

        return response()->json([
            'success' => true,
            'message' => 'Intervenant mis à jour avec succès'
        ]);
    }

    // Ajout de la méthode utilitaire
    private function updateCheckStatut($check)
    {
        $serviceStats = $check->serviceChecks()->select('statut')->get()->groupBy('statut');
        if (isset($serviceStats['error']) && $serviceStats['error']->count() > 0) {
            $check->update(['statut' => 'error']);
        } elseif (isset($serviceStats['warning']) && $serviceStats['warning']->count() > 0) {
            $check->update(['statut' => 'warning']);
        } elseif (isset($serviceStats['pending']) && $serviceStats['pending']->count() > 0) {
            $check->update(['statut' => 'pending']);
        } elseif (isset($serviceStats['in_progress']) && $serviceStats['in_progress']->count() > 0) {
            $check->update(['statut' => 'in_progress']);
        } else {
            $check->update(['statut' => 'success']);
        }
    }

    /**
     * Mettre à jour tous les service checks d'un check en lot
     */
    public function updateAll(Request $request, Check $check)
    {
        \Log::info('=== DÉBUT updateAll ===');
        \Log::info('Check ID:', ['id' => $check->id]);
        \Log::info('Données reçues:', $request->all());

        $request->validate([
            'service_checks' => 'required|array',
            'service_checks.*.id' => 'required|exists:service_checks,id',
            'service_checks.*.status' => 'required|in:pending,in_progress,success,warning,error',
            'service_checks.*.observations' => 'nullable|string|max:1000',
            'service_checks.*.intervenant_id' => 'nullable|exists:users,id'
        ]);

        try {
            foreach ($request->service_checks as $serviceCheckData) {
                \Log::info('Traitement service check:', $serviceCheckData);
                $serviceCheck = ServiceCheck::find($serviceCheckData['id']);
                
                if ($serviceCheck && $serviceCheck->check_id == $check->id) {
                    \Log::info('Avant mise à jour:', $serviceCheck->toArray());
                    $serviceCheck->update([
                        'statut' => $serviceCheckData['status'],
                        'observations' => $serviceCheckData['observations'] ?? null,
                        'intervenant' => $serviceCheckData['intervenant_id'] ?? null
                    ]);
                    \Log::info('Après mise à jour:', $serviceCheck->fresh()->toArray());
                } else {
                    \Log::warning('Service check non trouvé ou ne correspond pas au check:', $serviceCheckData);
                }
            }

            // Déterminer le statut global du check
            $serviceStats = $check->serviceChecks()->select('statut')->get()->groupBy('statut');
            if (isset($serviceStats['error']) && $serviceStats['error']->count() > 0) {
                $check->update(['statut' => 'error']);
                \Log::info('Check mis à jour vers error');
            } elseif (isset($serviceStats['warning']) && $serviceStats['warning']->count() > 0) {
                $check->update(['statut' => 'warning']);
                \Log::info('Check mis à jour vers warning');
            } elseif (isset($serviceStats['pending']) && $serviceStats['pending']->count() > 0) {
                $check->update(['statut' => 'pending']);
                \Log::info('Check mis à jour vers pending');
            } elseif (isset($serviceStats['in_progress']) && $serviceStats['in_progress']->count() > 0) {
                $check->update(['statut' => 'in_progress']);
                \Log::info('Check mis à jour vers in_progress');
            } else {
                $check->update(['statut' => 'success']);
                \Log::info('Check mis à jour vers success');
            }

            \Log::info('=== FIN updateAll ===');
            return response()->json([
                'success' => true,
                'message' => 'Tous les services ont été mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur dans updateAll:', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }
}
