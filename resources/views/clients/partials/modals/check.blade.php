<!-- Modal Ajout Check -->
<div class="modal fade" id="addCheckModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('checks.store') }}" method="POST">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Check</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date_time" class="form-label">Date et heure de vérification</label>
                        <input type="datetime-local" 
                               class="form-control @error('date_time') is-invalid @enderror" 
                               id="date_time" 
                               name="date_time" 
                               value="{{ old('date_time', now()->format('Y-m-d\TH:i')) }}"
                               required>
                        @error('date_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="statut" class="form-label">Statut initial</label>
                        <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                            <option value="pending" {{ old('statut') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="in_progress" {{ old('statut') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                            <option value="completed" {{ old('statut') == 'completed' ? 'selected' : '' }}>Terminé</option>
                            <option value="failed" {{ old('statut') == 'failed' ? 'selected' : '' }}>Échoué</option>
                        </select>
                        @error('statut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3"
                                  placeholder="Ajoutez des notes ou commentaires sur ce check...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Créer le check
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmation de Suppression -->
<div class="modal fade" id="deleteCheckModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Êtes-vous sûr de vouloir supprimer ce check ?</h5>
                <p class="text-muted mb-0">Cette action est irréversible et supprimera également tous les service checks associés.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteCheckForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCheck(checkId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteCheckModal'));
    const form = document.getElementById('deleteCheckForm');
    
    // Mettre à jour l'action du formulaire
    form.action = `/checks/${checkId}`;
    
    // Afficher la modale
    modal.show();
}
</script> 