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
                            <option value="">Aucune catégorie parente (catégorie principale)</option>
                            @foreach($client->categories->whereNull('category_pk') as $parentCategory)
                                <option value="{{ $parentCategory->id }}">{{ $parentCategory->title }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Sélectionnez une catégorie principale (sans parent) pour créer une sous-catégorie. Exemple : "Abonnements" pour créer "Licences"</small>
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
                            <option value="">Aucune catégorie parente (catégorie principale)</option>
                            @php
                                $currentCategoryId = isset($category) ? $category->id : null;
                                $excludedIds = isset($category) ? \App\Models\Category::where('category_pk', $category->id)->pluck('id')->toArray() : [];
                                $excludedIds[] = $currentCategoryId;
                            @endphp
                            @foreach($client->categories->whereNull('category_pk')->where('id', '!=', $currentCategoryId) as $parentCategory)
                                <option value="{{ $parentCategory->id }}">{{ $parentCategory->title }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Sélectionnez une catégorie principale (sans parent) pour créer une sous-catégorie</small>
                    </div>

                    <hr class="my-4">

                    <!-- Configuration des colonnes d'export pour cette catégorie -->
                    <div class="mb-3">
                        <h6 class="mb-3"><i class="bi bi-table me-2"></i>Colonnes à afficher dans les exports</h6>
                        <p class="text-muted small mb-3">Configurez les colonnes spécifiques à cette catégorie. Si non configuré, la configuration du template sera utilisée.</p>
                        
                        @php
                            $availableColumns = [
                                'description' => ['label' => 'Description', 'icon' => 'bi-file-text'],
                                'category_full_path' => ['label' => 'Catégorie complète', 'icon' => 'bi-folder'],
                                'statut' => ['label' => 'État', 'icon' => 'bi-check-circle'],
                                'expiration_date' => ['label' => 'Date d\'expiration', 'icon' => 'bi-calendar-x'],
                                'notes' => ['label' => 'Notes', 'icon' => 'bi-sticky'],
                                'observations' => ['label' => 'Observations', 'icon' => 'bi-eye'],
                                'intervenant' => ['label' => 'Intervenant', 'icon' => 'bi-person'],
                                'created_at' => ['label' => 'Date de vérification', 'icon' => 'bi-clock'],
                            ];
                            
                            $categoryExportColumns = old('export_columns', []);
                            $enabledColumns = [];
                            $columnLabels = [];
                            if (!empty($categoryExportColumns)) {
                                foreach ($categoryExportColumns as $col) {
                                    $enabledColumns[$col['field']] = true;
                                    $columnLabels[$col['field']] = $col['label'];
                                }
                            }
                        @endphp
                        
                        <div class="row g-2" id="categoryExportColumnsContainer">
                            @foreach($availableColumns as $field => $info)
                                <div class="col-md-6">
                                    <div class="card border h-100">
                                        <div class="card-body p-2">
                                            <div class="form-check">
                                                <input class="form-check-input category-column-checkbox" 
                                                       type="checkbox" 
                                                       name="category_export_columns_enabled[]" 
                                                       value="{{ $field }}"
                                                       id="cat_col_{{ $field }}"
                                                       data-field="{{ $field }}">
                                                <label class="form-check-label w-100" for="cat_col_{{ $field }}">
                                                    <i class="bi {{ $info['icon'] }} me-1"></i>
                                                    <strong>{{ $info['label'] }}</strong>
                                                </label>
                                            </div>
                                            <div class="mt-2 category-column-label-input" style="display: none;">
                                                <input type="text" 
                                                       class="form-control form-control-sm" 
                                                       name="category_export_columns_labels[{{ $field }}]"
                                                       placeholder="{{ $info['label'] }}">
                                                <input type="hidden" 
                                                       name="category_export_columns_order[]" 
                                                       value="{{ $field }}"
                                                       class="category-column-order-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Option pour afficher des statistiques -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_show_stats" name="show_stats" value="1">
                            <label class="form-check-label" for="edit_show_stats">
                                <strong>Afficher des statistiques pour cette catégorie</strong>
                                <small class="d-block text-muted">Exemple : Consommé, Total, Disponibles (pour les abonnements)</small>
                            </label>
                        </div>
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