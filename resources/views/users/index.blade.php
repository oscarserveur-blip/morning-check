@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Gestion des Utilisateurs</h5>
                            <p class="text-muted mb-0">Gérez les utilisateurs et leurs affectations aux clients</p>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('users.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Nouvel Utilisateur
                            </a>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <i class="bi bi-person me-2"></i>Utilisateur
                                    </th>
                                    <th>
                                        <i class="bi bi-envelope me-2"></i>Email
                                    </th>
                                    <th>
                                        <i class="bi bi-shield me-2"></i>Rôle
                                    </th>
                                    <th>
                                        <i class="bi bi-building me-2"></i>Clients assignés
                                    </th>
                                    <th>
                                        <i class="bi bi-calendar me-2"></i>Créé le
                                    </th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $user->name }}</h6>
                                                    <small class="text-muted">ID: {{ $user->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->role === 'admin')
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-shield-check me-1"></i>Admin
                                                </span>
                                            @else
                                                <span class="badge bg-info">
                                                    <i class="bi bi-person-gear me-1"></i>Gestionnaire
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->role === 'gestionnaire')
                                                <div class="d-flex flex-wrap gap-1">
                                                    @forelse($user->clients as $client)
                                                        <span class="badge bg-secondary">{{ $client->label }}</span>
                                                    @empty
                                                        <span class="text-muted">Aucun client assigné</span>
                                                    @endforelse
                                                </div>
                                            @else
                                                <span class="text-muted">Tous les clients</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $user->created_at->format('d/m/Y à H:i') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-info" title="Voir détails">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="Éditer">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                @if($user->id !== auth()->id())
                                                <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')" 
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-person-x fs-1"></i>
                                                <p class="mt-2">Aucun utilisateur trouvé</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}
</style>
@endsection 