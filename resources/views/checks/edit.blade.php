@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Modifier le Check</h5>
                            <p class="text-muted mb-0">Client: {{ $check->client->label }} - {{ $check->date_time->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('clients.show', ['client' => $check->client, 'tab' => 'checks']) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Retour
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('checks.update', $check) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="client_id" value="{{ $check->client_id }}">
                        
                        <div class="row">
                            <!-- Informations générales du check -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Informations générales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="date_time" class="form-label">Date et heure de vérification</label>
                                            <input type="datetime-local" 
                                                   class="form-control @error('date_time') is-invalid @enderror" 
                                                   id="date_time" 
                                                   name="date_time" 
                                                   value="{{ old('date_time', $check->date_time->format('Y-m-d\TH:i')) }}"
                                                   required>
                                            @error('date_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="statut" class="form-label">Statut du check</label>
                                            <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                                                <option value="pending" {{ old('statut', $check->statut) == 'pending' ? 'selected' : '' }}>En attente</option>
                                                <option value="in_progress" {{ old('statut', $check->statut) == 'in_progress' ? 'selected' : '' }}>En cours</option>
                                                <option value="completed" {{ old('statut', $check->statut) == 'completed' ? 'selected' : '' }}>Terminé</option>
                                                <option value="failed" {{ old('statut', $check->statut) == 'failed' ? 'selected' : '' }}>Échoué</option>
                                            </select>
                                            @error('statut')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Notes générales</label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                      id="notes" 
                                                      name="notes" 
                                                      rows="3"
                                                      placeholder="Notes générales sur ce check...">{{ old('notes', $check->notes) }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Services à vérifier -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Services à vérifier</h6>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addServiceCheck()">
                                            <i class="bi bi-plus-lg"></i> Ajouter un service
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="serviceChecksContainer">
                                            @forelse($check->serviceChecks as $index => $serviceCheck)
                                                <div class="service-check-item border rounded p-3 mb-3" data-index="{{ $index }}">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Service</label>
                                                            <select class="form-select" name="service_checks[{{ $index }}][service_id]" required>
                                                                <option value="">Sélectionner un service</option>
                                                                @foreach($check->client->services as $service)
                                                                    <option value="{{ $service->id }}" 
                                                                        {{ $serviceCheck->service_id == $service->id ? 'selected' : '' }}>
                                                                        {{ $service->title }} ({{ $service->category->title }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Statut</label>
                                                            <select class="form-select" name="service_checks[{{ $index }}][statut]" required>
                                                                <option value="pending" {{ $serviceCheck->statut == 'pending' ? 'selected' : '' }}>En attente</option>
                                                                <option value="in_progress" {{ $serviceCheck->statut == 'in_progress' ? 'selected' : '' }}>En cours</option>
                                                                <option value="success" {{ $serviceCheck->statut == 'success' ? 'selected' : '' }}>Succès</option>
                                                                <option value="warning" {{ $serviceCheck->statut == 'warning' ? 'selected' : '' }}>Avertissement</option>
                                                                <option value="error" {{ $serviceCheck->statut == 'error' ? 'selected' : '' }}>Erreur</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Intervenant</label>
                                                            <select class="form-select" name="service_checks[{{ $index }}][intervenant]">
                                                                <option value="">Aucun intervenant</option>
                                                                @foreach($users as $user)
                                                                    <option value="{{ $user->id }}" 
                                                                        {{ $serviceCheck->intervenant == $user->id ? 'selected' : '' }}>
                                                                        {{ $user->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Actions</label>
                                                            <div class="d-flex">
                                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeServiceCheck({{ $index }})">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <label class="form-label">Observations</label>
                                                            <textarea class="form-control" 
                                                                      name="service_checks[{{ $index }}][observations]" 
                                                                      rows="2"
                                                                      placeholder="Observations sur ce service...">{{ $serviceCheck->observations ?? $serviceCheck->commentaire }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <label class="form-label">Notes</label>
                                                            <textarea class="form-control" 
                                                                      name="service_checks[{{ $index }}][notes]" 
                                                                      rows="2"
                                                                      placeholder="Notes supplémentaires...">{{ $serviceCheck->notes }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="text-center text-muted py-4">
                                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                    <p>Aucun service vérifié</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('clients.show', ['client' => $check->client, 'tab' => 'checks']) }}" class="btn btn-light">
                                <i class="bi bi-x-lg me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let serviceCheckIndex = {{ $check->serviceChecks->count() }};

function addServiceCheck() {
    const container = document.getElementById('serviceChecksContainer');
    const template = `
        <div class="service-check-item border rounded p-3 mb-3" data-index="${serviceCheckIndex}">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Service</label>
                    <select class="form-select" name="service_checks[${serviceCheckIndex}][service_id]" required>
                        <option value="">Sélectionner un service</option>
                        @foreach($check->client->services as $service)
                            <option value="{{ $service->id }}">{{ $service->title }} ({{ $service->category->title }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="service_checks[${serviceCheckIndex}][statut]" required>
                        <option value="pending">En attente</option>
                        <option value="in_progress">En cours</option>
                        <option value="success">Succès</option>
                        <option value="warning">Avertissement</option>
                        <option value="error">Erreur</option>
                    </select>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="form-label">Intervenant</label>
                    <select class="form-select" name="service_checks[${serviceCheckIndex}][intervenant]">
                        <option value="">Aucun intervenant</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Actions</label>
                    <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeServiceCheck(${serviceCheckIndex})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <label class="form-label">Observations</label>
                    <textarea class="form-control" 
                              name="service_checks[${serviceCheckIndex}][observations]" 
                              rows="2"
                              placeholder="Observations sur ce service..."></textarea>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" 
                              name="service_checks[${serviceCheckIndex}][notes]" 
                              rows="2"
                              placeholder="Notes supplémentaires..."></textarea>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', template);
    serviceCheckIndex++;
}

function removeServiceCheck(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) {
        item.remove();
    }
}
</script>
@endsection 