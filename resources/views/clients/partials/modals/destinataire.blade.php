<!-- Modal Ajout Destinataire -->
<div class="modal fade" id="addDestinataireModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('rappel-destinataires.store') }}" method="POST">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un destinataire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="sender">Expéditeur</option>
                            <option value="receiver">Destinataire</option>
                            <option value="copie">Copie</option>
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

<!-- Modal Édition Destinataire -->
<div class="modal fade" id="editDestinataireModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editDestinataireForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le destinataire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type" class="form-label">Type</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="sender">Expéditeur</option>
                            <option value="receiver">Destinataire</option>
                            <option value="copie">Copie</option>
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