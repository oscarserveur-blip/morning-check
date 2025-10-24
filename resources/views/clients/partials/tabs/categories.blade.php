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
                    @foreach($client->categories as $category)
                        <a href="#" class="list-group-item list-group-item-action category-item" 
                           data-category-id="{{ $category->id }}"
                           onclick="loadServices({{ $category->id }})">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span>{{ $category->title }}</span>
                                    @if($category->parent)
                                        <small class="text-muted d-block">Parent: {{ $category->parent->title }}</small>
                                    @else
                                        <small class="text-muted d-block">Aucun parent</small>
                                    @endif
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory({{ $category->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCategory({{ $category->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </a>
                    @endforeach
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