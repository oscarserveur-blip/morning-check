<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\ServiceCheck;
use App\Mail\IntervenantAssignedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
        $check = \App\Models\Check::findOrFail($checkId);
        
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

        return response()->json([
            'service_checks' => $serviceChecks,
            'email_sent_at' => $check->email_sent_at ? $check->email_sent_at->toIso8601String() : null
        ]);
    }

    public function updateStatus(Request $request, ServiceCheck $serviceCheck)
    {
        // Vérifier si l'email a été envoyé
        if ($serviceCheck->check->email_sent_at) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier les statuts : l\'email a déjà été envoyé.'
            ], 403);
        }

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
        // Vérifier si l'email a été envoyé
        if ($serviceCheck->check->email_sent_at) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier les commentaires : l\'email a déjà été envoyé.'
            ], 403);
        }

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
        // Vérifier si l'email a été envoyé
        if ($serviceCheck->check->email_sent_at) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier les intervenants : l\'email a déjà été envoyé.'
            ], 403);
        }

        $request->validate([
            'intervenant_id' => 'nullable|exists:users,id'
        ]);

        $oldIntervenantId = $serviceCheck->intervenant;
        $newIntervenantId = $request->intervenant_id;

        $serviceCheck->update([
            'intervenant' => $newIntervenantId
        ]);

        // Envoyer un email à l'intervenant si un nouvel intervenant est assigné
        if ($newIntervenantId && $newIntervenantId != $oldIntervenantId) {
            try {
                $intervenant = User::find($newIntervenantId);
                if ($intervenant && $intervenant->email) {
                    // Recharger les relations nécessaires
                    $serviceCheck->load(['check.client', 'service']);
                    Mail::to($intervenant->email)->send(new IntervenantAssignedMail($serviceCheck, $intervenant));
                }
            } catch (\Exception $e) {
                \Log::error('Erreur envoi email intervenant: ' . $e->getMessage());
                // Ne pas bloquer la mise à jour si l'email échoue
            }
        }

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
        // Vérifier si l'email a été envoyé
        if ($check->email_sent_at) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier les statuts : l\'email a déjà été envoyé.'
            ], 403);
        }

        \Log::info('=== DÉBUT updateAll ===');
        \Log::info('Check ID:', ['id' => $check->id]);
        \Log::info('Données reçues:', $request->all());

        $validated = $request->validate([
            'service_checks' => 'required|array',
            'service_checks.*.id' => 'required|exists:service_checks,id',
            'service_checks.*.status' => 'required|in:pending,in_progress,success,warning,error',
            'service_checks.*.observations' => 'nullable|string|max:1000',
            'service_checks.*.intervenant_id' => 'nullable|exists:users,id'
        ], [
            'service_checks.required' => 'Aucun service check fourni.',
            'service_checks.*.id.required' => 'L\'ID du service check est requis.',
            'service_checks.*.id.exists' => 'Le service check spécifié n\'existe pas.',
            'service_checks.*.status.required' => 'Le statut est requis pour chaque service.',
            'service_checks.*.status.in' => 'Le statut doit être : pending, in_progress, success, warning ou error.',
        ]);

        // Validation supplémentaire : si statut est error, observations et intervenant sont requis
        foreach ($validated['service_checks'] as $index => $serviceCheckData) {
            if ($serviceCheckData['status'] === 'error') {
                if (empty($serviceCheckData['observations']) || !trim($serviceCheckData['observations'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Le commentaire est obligatoire pour les services avec le statut NOK (service check #{$serviceCheckData['id']})."
                    ], 422);
                }
                if (empty($serviceCheckData['intervenant_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "L'intervenant est obligatoire pour les services avec le statut NOK (service check #{$serviceCheckData['id']})."
                    ], 422);
                }
            }
        }

        try {
            foreach ($request->service_checks as $serviceCheckData) {
                \Log::info('Traitement service check:', $serviceCheckData);
                $serviceCheck = ServiceCheck::find($serviceCheckData['id']);
                
                if ($serviceCheck && $serviceCheck->check_id == $check->id) {
                    \Log::info('Avant mise à jour:', $serviceCheck->toArray());
                    $oldIntervenantId = $serviceCheck->intervenant;
                    $newIntervenantId = $serviceCheckData['intervenant_id'] ?? null;
                    
                    $serviceCheck->update([
                        'statut' => $serviceCheckData['status'],
                        'observations' => $serviceCheckData['observations'] ?? null,
                        'intervenant' => $newIntervenantId
                    ]);
                    \Log::info('Après mise à jour:', $serviceCheck->fresh()->toArray());
                    
                    // Envoyer un email à l'intervenant si un nouvel intervenant est assigné
                    if ($newIntervenantId && $newIntervenantId != $oldIntervenantId) {
                        try {
                            $intervenant = User::find($newIntervenantId);
                            if ($intervenant && $intervenant->email) {
                                // Recharger les relations nécessaires
                                $serviceCheck->load(['check.client', 'service']);
                                Mail::to($intervenant->email)->send(new IntervenantAssignedMail($serviceCheck, $intervenant));
                            }
                        } catch (\Exception $e) {
                            \Log::error('Erreur envoi email intervenant: ' . $e->getMessage());
                            // Ne pas bloquer la mise à jour si l'email échoue
                        }
                    }
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
