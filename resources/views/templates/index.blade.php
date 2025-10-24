@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Gestion des Templates</h5>
                            <p class="text-muted mb-0">Gérez vos modèles de bulletins</p>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('templates.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Nouveau Template
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
                    <div class="row g-4">
                        @forelse($templates as $template)
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
                                        <span class="fw-bold">{{ $template->name }}</span>
                                        <span class="badge bg-info">{{ strtoupper($template->type) }}</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <small class="text-muted">Description :</small>
                                            <div>{{ $template->description ?: 'Aucune description' }}</div>
                                        </div>
                                        
                                        <!-- Clients associés -->
                                        <div class="mb-2">
                                            <small class="text-muted">Clients associés :</small>
                                            <div>
                                                @if($template->clients->count() > 0)
                                                    @foreach($template->clients->take(3) as $client)
                                                        <span class="badge bg-secondary me-1">{{ $client->label }}</span>
                                                    @endforeach
                                                    @if($template->clients->count() > 3)
                                                        <span class="badge bg-light text-dark">+{{ $template->clients->count() - 3 }} autres</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">Aucun client associé</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">Type :</small>
                                            <div class="badge bg-info">{{ strtoupper($template->type) }}</div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-2">
                                        <div class="btn-group">
                                            <a href="{{ route('templates.edit', $template) }}" class="btn btn-sm btn-outline-primary" title="Éditer"><i class="bi bi-pencil"></i></a>
                                            <a href="{{ route('templates.duplicate', $template) }}" class="btn btn-sm btn-outline-secondary" title="Dupliquer"><i class="bi bi-files"></i></a>
                                            <a href="{{ route('templates.exportExample', $template) }}" class="btn btn-sm btn-outline-success" title="Exporter exemple"><i class="bi bi-file-earmark-excel"></i></a>
                                            <form action="{{ route('templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce template ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Aucun template trouvé
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 