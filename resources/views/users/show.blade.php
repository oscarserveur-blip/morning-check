@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div class="avatar-circle me-3">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $user->name }}</h5>
                            <p class="text-muted mb-0">{{ $user->email }}</p>
                        </div>
                        <div class="ms-auto">
                            @if($user->role === 'admin')
                                <span class="badge bg-primary fs-6">
                                    <i class="bi bi-shield-check me-1"></i>Administrateur
                                </span>
                            @else
                                <span class="badge bg-info fs-6">
                                    <i class="bi bi-person-gear me-1"></i>Gestionnaire
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Statistiques -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="bi bi-clipboard-check fs-1 text-primary mb-2"></i>
                                    <h3 class="mb-1">{{ $stats['total_checks'] }}</h3>
                                    <p class="text-muted mb-0">Checks créés</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="bi bi-calendar-month fs-1 text-success mb-2"></i>
                                    <h3 class="mb-1">{{ $stats['checks_this_month'] }}</h3>
                                    <p class="text-muted mb-0">Checks ce mois</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="bi bi-building fs-1 text-info mb-2"></i>
                                    <h3 class="mb-1">{{ $stats['assigned_clients'] }}</h3>
                                    <p class="text-muted mb-0">Clients assignés</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Clients assignés -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-building me-2"></i>Clients assignés
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($user->role === 'admin')
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Cet administrateur a accès à tous les clients.
                                        </div>
                                    @elseif($user->clients->count() > 0)
                                        <div class="list-group list-group-flush">
                                            @foreach($user->clients as $client)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $client->label }}</strong>
                                                        @if($client->template)
                                                            <br><small class="text-muted">{{ $client->template->name }}</small>
                                                        @endif
                                                    </div>
                                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Aucun client n'est assigné à ce gestionnaire.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Derniers checks -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-clock-history me-2"></i>Derniers checks créés
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($recentChecks->count() > 0)
                                        <div class="list-group list-group-flush">
                                            @foreach($recentChecks as $check)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $check->client->label }}</strong>
                                                        <br><small class="text-muted">{{ $check->created_at->format('d/m/Y à H:i') }}</small>
                                                    </div>
                                                    <div>
                                                        @if($check->statut === 'completed')
                                                            <span class="badge bg-success">Terminé</span>
                                                        @elseif($check->statut === 'pending')
                                                            <span class="badge bg-warning">En attente</span>
                                                        @else
                                                            <span class="badge bg-danger">Échec</span>
                                                        @endif
                                                        <a href="{{ route('checks.show', $check) }}" class="btn btn-sm btn-outline-primary ms-2">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Cet utilisateur n'a créé aucun check pour le moment.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-info-circle me-2"></i>Informations utilisateur
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Nom :</strong> {{ $user->name }}<br>
                                            <strong>Email :</strong> {{ $user->email }}<br>
                                            <strong>Rôle :</strong> {{ ucfirst($user->role) }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Créé le :</strong> {{ $user->created_at->format('d/m/Y à H:i') }}<br>
                                            <strong>Dernière modification :</strong> {{ $user->updated_at->format('d/m/Y à H:i') }}<br>
                                            <strong>Email vérifié :</strong> 
                                            @if($user->email_verified_at)
                                                <span class="badge bg-success">Oui</span>
                                            @else
                                                <span class="badge bg-warning">Non</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary me-2">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}
</style>
@endsection 