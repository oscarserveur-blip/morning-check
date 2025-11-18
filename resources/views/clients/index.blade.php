@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Gestion des Clients</h5>
                            <p class="text-muted mb-0">Gérez vos clients et leurs informations</p>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Nouveau Client
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
                    <!-- Filtres et recherche -->
                    <div class="mb-4 bg-light p-3 rounded">
                        <form method="GET" action="{{ route('clients.index') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Recherche</label>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       placeholder="Nom du client..."
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-3">
                                <label for="template_id" class="form-label">Template</label>
                                <select id="template_id" name="template_id" class="form-select form-select-sm">
                                    <option value="">Tous les templates</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ request('template_id') == $template->id ? 'selected' : '' }}>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="sort_by" class="form-label">Trier par</label>
                                <select id="sort_by" name="sort_by" class="form-select form-select-sm">
                                    <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Date création</option>
                                    <option value="updated_at" {{ request('sort_by') === 'updated_at' ? 'selected' : '' }}>Dernière modif</option>
                                    <option value="label" {{ request('sort_by') === 'label' ? 'selected' : '' }}>Nom</option>
                                    <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>ID</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="sort_order" class="form-label">Ordre</label>
                                <select id="sort_order" name="sort_order" class="form-select form-select-sm">
                                    <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>Plus récent</option>
                                    <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Plus ancien</option>
                                </select>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Statistiques -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <small>Total clients</small>
                                            <h6 class="mb-0">{{ $clients->total() }}</h6>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-people"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <small>Avec template</small>
                                            <h6 class="mb-0">{{ $clients->getCollection()->whereNotNull('template_id')->count() }}</h6>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <small>Avec logo</small>
                                            <h6 class="mb-0">{{ $clients->getCollection()->whereNotNull('logo')->count() }}</h6>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <small>Cette page</small>
                                            <h6 class="mb-0">{{ $clients->count() }}</h6>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-list"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <form method="GET" action="" class="d-flex align-items-center gap-2">
                            <label for="per_page" class="me-2">Afficher</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                @foreach([5, 10, 15, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                            <span class="ms-2">par page</span>
                            @foreach(request()->except('per_page', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                        </form>
                    </div>

                    <style>
                        .table tbody tr[onclick] {
                            cursor: pointer;
                        }
                        .table tbody tr[onclick]:hover {
                            background-color: rgba(74,144,226,0.08) !important;
                        }
                    </style>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Client</th>
                                    <th scope="col">Logo</th>
                                    <th scope="col">Template</th>
                                    <th scope="col">Heure de vérification</th>
                                    <th scope="col">Date création</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clients as $client)
                                    <tr style="cursor: pointer;" onclick="window.location.href='{{ route('clients.show', $client) }}'">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="ms-3">
                                                    <h6 class="mb-0">{{ $client->label }}</h6>
                                                    <small class="text-muted">ID: #{{ $client->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($client->logo)
                                                <img src="{{ asset('storage/' . $client->logo) }}" 
                                                     alt="Logo" 
                                                     class="rounded-circle" 
                                                     width="40" 
                                                     height="40"
                                                     style="object-fit: cover;">
                                            @else
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-building text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $client->template->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $client->check_time }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $client->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-end" onclick="event.stopPropagation();">
                                            <div class="btn-group">
                                                <a href="{{ route('clients.show', $client) }}" 
                                                   class="btn btn-sm btn-outline-info"
                                                   onclick="event.stopPropagation();">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); openEditClientModal({{ $client->id }});">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="{{ route('clients.duplicate', $client) }}" class="btn btn-sm btn-outline-secondary" title="Dupliquer" onclick="event.stopPropagation();">
                                                    <i class="bi bi-files"></i>
                                                </a>
                                                <form action="{{ route('clients.destroy', $client) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?')"
                                                      onclick="event.stopPropagation();">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                <h6>Aucun client trouvé</h6>
                                                <p class="mb-0">Aucun client ne correspond à vos critères de recherche.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal édition client -->
<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editClientModalBody">
                <!-- Formulaire chargé dynamiquement -->
                <div class="text-center text-muted py-5">
                    <div class="spinner-border"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openEditClientModal(clientId) {
    const modal = new bootstrap.Modal(document.getElementById('editClientModal'));
    document.getElementById('editClientModalBody').innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border"></div></div>';
    modal.show();
    fetch(`/clients/${clientId}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.text())
        .then(html => {
            document.getElementById('editClientModalBody').innerHTML = html;
            // Gérer la soumission AJAX du formulaire
            const form = document.querySelector('#editClientModalBody form');
            if (form) {
                form.onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            // Afficher les erreurs
                            document.getElementById('editClientModalBody').innerHTML = data.html || 'Erreur lors de la mise à jour.';
                        }
                    })
                    .catch(() => alert('Erreur lors de la mise à jour.'));
                };
            }
        });
}
</script>
@endpush
@endsection 