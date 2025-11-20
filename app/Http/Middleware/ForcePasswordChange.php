<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié et doit changer son mot de passe
        if (auth()->check() && auth()->user()->must_change_password) {
            // Ne pas rediriger si on est déjà sur la page de changement de mot de passe
            if (!$request->routeIs('password.force-change')) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
