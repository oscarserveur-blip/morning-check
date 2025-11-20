<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ForcePasswordChangeController extends Controller
{
    /**
     * Show the form for changing password.
     */
    public function show()
    {
        if (!auth()->check() || !auth()->user()->must_change_password) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.force-password-change');
    }

    /**
     * Handle the password change request.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ]);

        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Votre mot de passe a été modifié avec succès.');
    }
}
