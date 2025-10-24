<?php

namespace App\Http\Traits;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

trait ManagesUserPermissions
{
    /**
     * Filtre les clients selon les permissions de l'utilisateur connecté
     */
    protected function filterClientsByUserPermissions(Builder $query)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('id', $clientIds);
        }
        
        return $query;
    }

    /**
     * Vérifie si l'utilisateur connecté peut accéder à un client
     */
    protected function authorizeClientAccess(Client $client)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire() && !$user->clients->contains($client->id)) {
            abort(403, 'Vous n\'avez pas accès à ce client.');
        }
    }

    /**
     * Récupère les clients accessibles à l'utilisateur connecté
     */
    protected function getAccessibleClients()
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return Client::all();
        }
        
        if ($user->isGestionnaire()) {
            return $user->clients;
        }
        
        return collect();
    }

    /**
     * Filtre les checks selon les permissions de l'utilisateur connecté
     */
    protected function filterChecksByUserPermissions(Builder $query)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }
        
        return $query;
    }

    /**
     * Filtre les catégories selon les permissions de l'utilisateur connecté
     */
    protected function filterCategoriesByUserPermissions(Builder $query)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }
        
        return $query;
    }

    /**
     * Filtre les services selon les permissions de l'utilisateur connecté
     */
    protected function filterServicesByUserPermissions(Builder $query)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereHas('category.client', function($q) use ($clientIds) {
                $q->whereIn('id', $clientIds);
            });
        }
        
        return $query;
    }

    /**
     * Filtre les mailings selon les permissions de l'utilisateur connecté
     */
    protected function filterMailingsByUserPermissions(Builder $query)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }
        
        return $query;
    }

    /**
     * Filtre les destinataires selon les permissions de l'utilisateur connecté
     */
    protected function filterDestinatairesByUserPermissions(Builder $query)
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }
        
        return $query;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une ressource liée à un client
     */
    protected function authorizeResourceAccess($resource, $clientIdField = 'client_id')
    {
        $user = auth()->user();
        
        if ($user->isGestionnaire()) {
            $clientId = $resource->$clientIdField ?? $resource->client->id ?? null;
            
            if (!$clientId || !$user->clients->contains($clientId)) {
                abort(403, 'Vous n\'avez pas accès à cette ressource.');
            }
        }
    }
} 