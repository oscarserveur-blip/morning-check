<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Mail\UserPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
    {
        // Vérification simple dans le constructeur
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Accès refusé. Seuls les administrateurs peuvent gérer les utilisateurs.');
        }
    }

    public function index()
    {
        try {
            $users = User::with('clients')->get();
            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            \Log::error('Erreur dans UserController@index: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            abort(500, 'Erreur lors du chargement des utilisateurs: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $clients = Client::all();
        return view('users.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,gestionnaire',
            'clients' => 'array',
            'clients.*' => 'exists:clients,id',
        ]);

        // Générer un mot de passe aléatoire sécurisé
        $password = Str::random(16);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($password),
            'role' => $validated['role'],
            'must_change_password' => true, // Forcer le changement à la première connexion
        ]);

        if ($user->role === 'gestionnaire' && !empty($validated['clients'])) {
            $user->clients()->sync($validated['clients']);
        }

        // Envoyer le mot de passe par email
        try {
            Mail::to($user->email)->send(new UserPasswordMail($user, $password));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email mot de passe: ' . $e->getMessage());
            // Ne pas bloquer la création si l'email échoue
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès. Le mot de passe a été envoyé par email.');
    }

    public function edit(User $user)
    {
        $clients = Client::all();
        $user->load('clients');
        return view('users.edit', compact('user', 'clients'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,gestionnaire',
            'clients' => 'array',
            'clients.*' => 'exists:clients,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        if ($user->role === 'gestionnaire') {
            $user->clients()->sync($validated['clients'] ?? []);
        } else {
            $user->clients()->detach();
        }
        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }

    public function show(User $user)
    {
        $user->load('clients', 'checks');
        
        // Statistiques de l'utilisateur
        $stats = [
            'total_checks' => $user->checks->count(),
            'checks_this_month' => $user->checks()->whereMonth('created_at', now()->month)->count(),
            'assigned_clients' => $user->clients->count(),
        ];

        // Derniers checks créés par cet utilisateur
        $recentChecks = $user->checks()
            ->with(['client', 'serviceChecks'])
            ->latest()
            ->take(10)
            ->get();

        return view('users.show', compact('user', 'stats', 'recentChecks'));
    }

    /**
     * Envoie un email de réinitialisation de mot de passe à l'utilisateur
     */
    public function sendPasswordReset(User $user)
    {
        try {
            $status = Password::sendResetLink(
                ['email' => $user->email]
            );

            if ($status == Password::RESET_LINK_SENT) {
                return redirect()->route('users.index')
                    ->with('success', "Un email de réinitialisation de mot de passe a été envoyé à {$user->email}.");
            } else {
                return redirect()->route('users.index')
                    ->with('error', "Erreur lors de l'envoi de l'email de réinitialisation.");
            }
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email réinitialisation mot de passe: ' . $e->getMessage());
            return redirect()->route('users.index')
                ->with('error', "Erreur lors de l'envoi de l'email : " . $e->getMessage());
        }
    }
} 