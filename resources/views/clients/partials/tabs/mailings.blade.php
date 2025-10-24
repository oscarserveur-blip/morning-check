<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Mailings</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMailingModal">
                        <i class="bi bi-plus-lg"></i> Ajouter un mailing
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($client->mailings as $mailing)
                                <tr>
                                    <td>{{ $mailing->email }}</td>
                                    <td>
                                        @switch($mailing->type)
                                            @case('sender')
                                                <span class="badge bg-primary">Expéditeur</span>
                                                @break
                                            @case('receiver')
                                                <span class="badge bg-success">Destinataire</span>
                                                @break
                                            @case('copie')
                                                <span class="badge bg-info">Copie</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $mailing->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editMailing({{ $mailing->id }})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteMailing({{ $mailing->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Aucun mailing trouvé
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

@include('clients.partials.modals.mailing') 