<!-- Modal Ajout Catégorie -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCategoryForm" action="{{ route('categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_pk" class="form-label">Catégorie parente</label>
                        <select class="form-select" id="category_pk" name="category_pk">
                            <option value="">Aucune catégorie parente</option>
                            @foreach($client->categories as $parentCategory)
                                <option value="{{ $parentCategory->id }}">{{ $parentCategory->title }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Laissez vide si cette catégorie n'a pas de parent</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Catégorie -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_pk" class="form-label">Catégorie parente</label>
                        <select class="form-select" id="edit_category_pk" name="category_pk">
                            <option value="">Aucune catégorie parente</option>
                            @foreach($client->categories as $parentCategory)
                                <option value="{{ $parentCategory->id }}">{{ $parentCategory->title }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Laissez vide si cette catégorie n'a pas de parent</small>
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