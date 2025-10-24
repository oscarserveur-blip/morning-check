<!-- Modal Ajout Service Check -->
<div class="modal fade" id="addServiceCheckModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('service-checks.store') }}" method="POST">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un service check</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_id" class="form-label">Service</label>
                        <select class="form-select" id="service_id" name="service_id" required>
                            <option value="">Sélectionner un service</option>
                            @foreach($client->services as $service)
                                <option value="{{ $service->id }}">{{ $service->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="check_id" class="form-label">Check</label>
                        <select class="form-select" id="check_id" name="check_id" required>
                            <option value="">Sélectionner un check</option>
                            @foreach($client->checks as $check)
                                <option value="{{ $check->id }}">{{ $check->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Service Check -->
<div class="modal fade" id="editServiceCheckModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editServiceCheckForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le service check</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_service_id" class="form-label">Service</label>
                        <select class="form-select" id="edit_service_id" name="service_id" required>
                            <option value="">Sélectionner un service</option>
                            @foreach($client->services as $service)
                                <option value="{{ $service->id }}">{{ $service->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_check_id" class="form-label">Check</label>
                        <select class="form-select" id="edit_check_id" name="check_id" required>
                            <option value="">Sélectionner un check</option>
                            @foreach($client->checks as $check)
                                <option value="{{ $check->id }}">{{ $check->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Statut</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div> 