<div class="row">
    <!-- Liste des catégories -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Catégories</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="categoriesList">
                    @php
                        // Séparer les catégories parent et enfants
                        $parentCategories = $client->categories->whereNull('category_pk');
                        $childCategories = $client->categories->whereNotNull('category_pk');
                    @endphp
                    
                    @foreach($parentCategories as $parentCategory)
                        <a href="#" class="list-group-item list-group-item-action category-item" 
                           data-category-id="{{ $parentCategory->id }}"
                           onclick="loadServices({{ $parentCategory->id }})"
                           style="background-color: #f8f9fa; font-weight: 600;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span><i class="bi bi-folder-fill me-2"></i>{{ $parentCategory->title }}</span>
                                    <small class="text-muted d-block">Catégorie principale</small>
                                    @php
                                        $childrenCount = $client->categories->where('category_pk', $parentCategory->id)->count();
                                    @endphp
                                    @if($childrenCount > 0)
                                        <small class="text-info d-block">
                                            <i class="bi bi-arrow-down-right"></i> {{ $childrenCount }} sous-catégorie(s)
                                        </small>
                                    @endif
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory({{ $parentCategory->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCategory({{ $parentCategory->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </a>
                        
                        @foreach($childCategories->where('category_pk', $parentCategory->id) as $childCategory)
                            <a href="#" class="list-group-item list-group-item-action category-item" 
                               data-category-id="{{ $childCategory->id }}"
                               onclick="loadServices({{ $childCategory->id }})"
                               style="padding-left: 2.5rem; border-left: 3px solid #0d6efd;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span><i class="bi bi-folder me-2"></i>{{ $childCategory->title }}</span>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-arrow-up-right"></i> Parent: {{ $parentCategory->title }}
                                        </small>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editCategory({{ $childCategory->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteCategory({{ $childCategory->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @endforeach
                    
                    @if($parentCategories->isEmpty())
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Aucune catégorie créée
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des services -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Services</h6>
                    <button type="button" class="btn btn-sm btn-primary" id="addServiceBtn" data-bs-toggle="modal" data-bs-target="#addServiceModal" disabled>
                        <i class="bi bi-plus-lg"></i> Ajouter un service
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="servicesList">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-arrow-left-circle fs-1 d-block mb-2"></i>
                        Sélectionnez une catégorie pour voir ses services
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('clients.partials.modals.category')
@include('clients.partials.modals.service')
@include('clients.partials.modals.delete-confirm')

@push('scripts')
<script>
let selectedCategoryId = null;

// Désactive le bouton au chargement
const addServiceBtn = document.getElementById('addServiceBtn');
if (addServiceBtn) addServiceBtn.disabled = true;

// Quand on clique sur une catégorie, active le bouton et stocke l'id
const categoryItems = document.querySelectorAll('.category-item');
categoryItems.forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        selectedCategoryId = this.getAttribute('data-category-id');
        if (addServiceBtn) addServiceBtn.disabled = false;
        // Met à jour le champ hidden du modal
        document.getElementById('service_category_id').value = selectedCategoryId;
    });
});

// Après ajout d'un service, vide le champ titre
const addServiceForm = document.getElementById('addServiceForm');
if (addServiceForm) {
    addServiceForm.addEventListener('submit', function() {
        setTimeout(() => {
            document.getElementById('service_title').value = '';
        }, 200); // Laisse le temps au modal de se fermer
    });
}

// Quand on ferme le modal, vide le champ titre
const addServiceModal = document.getElementById('addServiceModal');
if (addServiceModal) {
    addServiceModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('service_title').value = '';
    });
}
</script>
@endpush 