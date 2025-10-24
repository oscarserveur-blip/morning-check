<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role = null, $permission = null)
    {
        if (!auth()->check()) {
            abort(403, 'Accès refusé.');
        }

        $user = auth()->user();

        // Vérifier le rôle si spécifié
        if ($role && $user->role !== $role) {
            abort(403, 'Accès refusé.');
        }

        // Vérifier les permissions spécifiques pour les gestionnaires
        if ($permission === 'client.access' && $user->isGestionnaire()) {
            $clientId = $request->route('client')?->id ?? $request->route('client');
            
            if ($clientId && !$user->clients->contains($clientId)) {
                abort(403, 'Vous n\'avez pas accès à ce client.');
            }
        }

        return $next($request);
    }
} 