<!-- Modal de Confirmation de Suppression -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
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
                <h5 class="mt-3">Êtes-vous sûr de vouloir supprimer cet élément ?</h5>
                <p class="text-muted mb-0">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-2"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div> 