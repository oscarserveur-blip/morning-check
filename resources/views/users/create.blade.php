@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0">Créer un nouvel utilisateur</h5>
                            <p class="text-muted mb-0">Ajoutez un nouvel utilisateur et gérez ses permissions</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('users.store') }}" method="POST" id="userForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="bi bi-person me-1"></i>Nom complet <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" required value="{{ old('name') }}"
                                           placeholder="Ex: Jean Dupont">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope me-1"></i>Adresse email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" required value="{{ old('email') }}"
                                           placeholder="jean.dupont@example.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Information :</strong> Un mot de passe temporaire sera généré automatiquement et envoyé par email à l'utilisateur. 
                            Il devra le changer lors de sa première connexion.
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label">
                                <i class="bi bi-shield me-1"></i>Rôle <span class="text-danger">*</span>
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="radio" name="role" id="roleAdmin" value="admin" 
                                               {{ old('role') === 'admin' ? 'checked' : '' }} onchange="toggleClientsSelect()">
                                        <label class="form-check-label card-check-label" for="roleAdmin">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <i class="bi bi-shield-check fs-1 text-primary"></i>
                                                    <h6 class="mt-2">Administrateur</h6>
                                                    <small class="text-muted">Accès complet à toutes les fonctionnalités</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="radio" name="role" id="roleGestionnaire" value="gestionnaire" 
                                               {{ old('role') === 'gestionnaire' ? 'checked' : '' }} onchange="toggleClientsSelect()">
                                        <label class="form-check-label card-check-label" for="roleGestionnaire">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <i class="bi bi-person-gear fs-1 text-info"></i>
                                                    <h6 class="mt-2">Gestionnaire</h6>
                                                    <small class="text-muted">Accès limité aux clients assignés</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4" id="clientsSelectContainer" style="display:none;">
                            <label for="clients" class="form-label">
                                <i class="bi bi-building me-1"></i>Clients à gérer
                            </label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                @foreach($clients as $client)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="clients[]" 
                                               value="{{ $client->id }}" id="client{{ $client->id }}"
                                               {{ in_array($client->id, old('clients', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="client{{ $client->id }}">
                                            <strong>{{ $client->label }}</strong>
                                            @if($client->template)
                                                <span class="badge bg-light text-dark ms-2">{{ $client->template->name }}</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Sélectionnez les clients que cet utilisateur pourra gérer
                            </small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Créer l'utilisateur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-check-card {
    margin-bottom: 0;
}

.card-check-label {
    cursor: pointer;
    width: 100%;
}

.form-check-card .card {
    transition: all 0.2s;
    border: 2px solid #dee2e6;
}

.form-check-card .form-check-input:checked + .card-check-label .card {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-check-card .form-check-input {
    position: absolute;
    opacity: 0;
}
</style>

<script>
function toggleClientsSelect() {
    const roleGestionnaire = document.getElementById('roleGestionnaire');
    const clientsContainer = document.getElementById('clientsSelectContainer');
    
    if (roleGestionnaire && roleGestionnaire.checked) {
        clientsContainer.style.display = 'block';
    } else {
        clientsContainer.style.display = 'none';
        // Décocher tous les clients si on passe en admin
        const checkboxes = clientsContainer.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleClientsSelect();
});
</script>
@endsection 