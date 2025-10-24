<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Service Checks</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceCheckModal">
                        <i class="bi bi-plus-lg"></i> Ajouter un service check
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Check</th>
                                <th>Statut</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($client->serviceChecks as $serviceCheck)
                                <tr>
                                    <td>{{ $serviceCheck->service->title }}</td>
                                    <td>{{ $serviceCheck->check->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $serviceCheck->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ $serviceCheck->status === 'active' ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td>{{ $serviceCheck->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editServiceCheck({{ $serviceCheck->id }})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteServiceCheck({{ $serviceCheck->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Aucun service check trouvé
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

@include('clients.partials.modals.service-check') 