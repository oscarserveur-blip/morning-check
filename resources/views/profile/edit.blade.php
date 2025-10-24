@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            @if (session('status') === 'profile-updated')
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ __('Profile updated successfully.') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Informations du profil</h5>
                    <p class="text-muted mb-0">Mettez à jour les informations de votre profil et votre adresse e-mail.</p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('profile.update') }}" class="row g-3">
                        @csrf
                        @method('patch')

                        <div class="col-12">
                            <label for="name" class="form-label">Nom</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Mettre à jour le mot de passe</h5>
                    <p class="text-muted mb-0">Assurez-vous que votre compte utilise un mot de passe long et aléatoire pour rester sécurisé.</p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('password.update') }}" class="row g-3">
                        @csrf
                        @method('put')

                        <div class="col-12">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                                       id="current_password" 
                                       name="current_password" 
                                       required>
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required>
                                @error('password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-key-fill"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Mettre à jour le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Supprimer le compte</h5>
                    <p class="text-muted mb-0">Une fois votre compte supprimé, toutes ses ressources et données seront définitivement effacées.</p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('profile.destroy') }}" class="row g-3">
                        @csrf
                        @method('delete')

                        <div class="col-12">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required>
                                @error('password', 'userDeletion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Veuillez entrer votre mot de passe pour confirmer la suppression de votre compte.</div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i>Supprimer le compte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
