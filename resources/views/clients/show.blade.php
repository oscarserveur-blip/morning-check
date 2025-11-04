@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Détails du Client</h5>
                            <p class="text-muted mb-0">{{ $client->label }}</p>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Retour
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Informations du client -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Informations du client</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            @if($client->logo)
                                                <img src="{{ asset('storage/' . $client->logo) }}" 
                                                     alt="{{ $client->label }}" 
                                                     class="img-fluid rounded" 
                                                     style="max-height: 120px; max-width: 100%;">
                                            @else
                                                <div class="bg-white rounded d-flex align-items-center justify-content-center" 
                                                     style="height: 120px; width: 100%;">
                                                    <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-8">
                                            <p><strong>Nom:</strong> {{ $client->label }}</p>
                                            @if($client->template)
                                                <p><strong>Template:</strong> {{ $client->template->name }}</p>
                                            @endif
                                            @if($client->check_time)
                                                <p><strong>Heure de vérification:</strong> {{ $client->check_time }}</p>
                                            @endif
                                            <p><strong>Date de création:</strong> {{ $client->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Statistiques</h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border-end">
                                                <h4 class="text-primary mb-0">{{ $client->categories->count() }}</h4>
                                                <small class="text-muted">Catégories</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border-end">
                                                <h4 class="text-success mb-0">{{ $client->services->count() }}</h4>
                                                <small class="text-muted">Services</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="text-info mb-0">{{ $client->checks->count() }}</h4>
                                            <small class="text-muted">Checks</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ request('tab', 'categories') === 'categories' ? 'active' : '' }}" 
                                id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                                <i class="bi bi-tags me-2"></i>Catégories de Services
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ request('tab') === 'mailings' ? 'active' : '' }}" id="mailings-tab" data-bs-toggle="tab" data-bs-target="#mailings" type="button" role="tab">
                                <i class="bi bi-envelope me-2"></i>Mailings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ request('tab') === 'destinataires' ? 'active' : '' }}" id="destinataires-tab" data-bs-toggle="tab" data-bs-target="#destinataires" type="button" role="tab">
                                <i class="bi bi-people me-2"></i>Destinataires Rappels
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ request('tab') === 'checks' ? 'active' : '' }}" id="checks-tab" data-bs-toggle="tab" data-bs-target="#checks" type="button" role="tab">
                                <i class="bi bi-check-square me-2"></i>Checks
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4" id="clientTabsContent">
                        <div class="tab-pane fade {{ request('tab', 'categories') === 'categories' ? 'show active' : '' }}" id="categories" role="tabpanel">
                            @include('clients.partials.tabs.categories')
                        </div>
                        <div class="tab-pane fade {{ request('tab') === 'mailings' ? 'show active' : '' }}" id="mailings" role="tabpanel">
                            @include('clients.partials.tabs.mailings')
                        </div>
                        <div class="tab-pane fade {{ request('tab') === 'destinataires' ? 'show active' : '' }}" id="destinataires" role="tabpanel">
                            @include('clients.partials.tabs.destinataires')
                        </div>
                        <div class="tab-pane fade {{ request('tab') === 'checks' ? 'show active' : '' }}" id="checks" role="tabpanel">
                            @include('clients.partials.tabs.checks')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- Modal Ajout Service -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addServiceForm" action="{{ route('services.store') }}" method="POST">
                @csrf
                <input type="hidden" name="category_id" id="service_category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="service_title" name="title" required>
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

<!-- Modal Édition Service -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editServiceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_service_title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="edit_service_title" name="title" required>
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
                <h5 class="mt-3">Êtes-vous sûr de vouloir supprimer ce service ?</h5>
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

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-info-circle me-2"></i>
            <strong class="me-auto" id="toast-title">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toast-message"></div>
    </div>
</div>

<!-- Modal pour voir les services d'un check -->
<div class="modal fade" id="viewCheckModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Services à vérifier</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm ms-3" id="manageServicesBtn" style="display:inline-block;">
                    <i class="bi bi-pencil-square me-1"></i> Gérer les services
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="serviceCheckList">
                    <!-- Les services seront groupés par catégorie ici -->
                </div>
            </div>
            <div class="modal-footer">
                <!-- Les boutons seront ajoutés dynamiquement par JavaScript -->
            </div>
        </div>
    </div>
</div>

@include('clients.partials.toast')
@push('scripts')
<script>
// Fonction globale pour afficher un toast
function showToast(title, message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastTitle = document.getElementById('toast-title');
    const toastMessage = document.getElementById('toast-message');
    const icons = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-exclamation-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
    };
    toastTitle.innerHTML = `<i class=\"bi ${icons[type]} me-2\"></i>${title}`;
    toastMessage.textContent = message;
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

function loadServices(categoryId) {
    // Activer le bouton d'ajout de service
    document.getElementById('addServiceBtn').disabled = false;
    
    // Mettre à jour l'ID de la catégorie dans le formulaire d'ajout de service
    document.getElementById('service_category_id').value = categoryId;
    
    // Charger les services de la catégorie
    fetch(`/categories/${categoryId}/services`)
        .then(response => response.json())
        .then(services => {
            const servicesList = document.getElementById('servicesList');
            if (services.length === 0) {
                servicesList.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Aucun service dans cette catégorie
                    </div>
                `;
                return;
            }
            
            servicesList.innerHTML = `
                <div class="list-group">
                    ${services.map(service => `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">${service.title}</h6>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editService(${service.id}, '${service.title}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteService(${service.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des services:', error);
            showToast('Erreur', 'Une erreur est survenue lors du chargement des services', 'error');
        });
}

// Fonction pour éditer une catégorie
function editCategory(categoryId) {
    event.stopPropagation();
    fetch(`/categories/${categoryId}`)
        .then(response => response.json())
        .then(data => {
            const form = document.getElementById('editCategoryForm');
            form.action = `/categories/${categoryId}`;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_category_pk').value = data.category_pk;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        });
}

// Fonction pour supprimer une catégorie
function deleteCategory(categoryId) {
    event.stopPropagation();
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        fetch(`/categories/${categoryId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(() => {
            window.location.reload();
        });
    }
}

// Fonction pour éditer un service
function editService(serviceId, currentTitle) {
    const modal = new bootstrap.Modal(document.getElementById('editServiceModal'));
    const form = document.getElementById('editServiceForm');
    const titleInput = document.getElementById('edit_service_title');
    
    // Mettre à jour le formulaire
    form.action = `/services/${serviceId}`;
    titleInput.value = currentTitle;
    
    // Afficher le modal
    modal.show();
    
    // Gérer la soumission du formulaire
    form.onsubmit = function(e) {
        e.preventDefault();
        
        fetch(form.action, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                title: titleInput.value
            })
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            showToast('Succès', 'Service modifié avec succès', 'success');
            // Recharger les services
            const categoryId = document.getElementById('service_category_id').value;
            loadServices(categoryId);
        })
        .catch(error => {
            console.error('Erreur lors de la modification du service:', error);
            showToast('Erreur', 'Une erreur est survenue lors de la modification du service', 'error');
        });
    };
}

// Fonction pour supprimer un service
function deleteService(serviceId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Supprimer l'ancien gestionnaire d'événements s'il existe
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Ajouter le nouveau gestionnaire d'événements
    newConfirmBtn.addEventListener('click', function() {
        fetch(`/services/${serviceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                showToast('Succès', 'Service supprimé avec succès', 'success');
                // Recharger les services de la catégorie actuelle
                const categoryId = document.getElementById('service_category_id').value;
                loadServices(categoryId);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression du service:', error);
            showToast('Erreur', 'Une erreur est survenue lors de la suppression du service', 'error');
        });
    });
    
    // Afficher la modale
    modal.show();
}

// Gérer la création d'un service
document.getElementById('addServiceForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addServiceModal'));
        modal.hide();
        showToast('Succès', 'Service créé avec succès', 'success');
        // Recharger les services
        const categoryId = document.getElementById('service_category_id').value;
        loadServices(categoryId);
    })
    .catch(error => {
        console.error('Erreur lors de la création du service:', error);
        showToast('Erreur', 'Une erreur est survenue lors de la création du service', 'error');
    });
};

function editMailing(id) {
    fetch(`/mailings/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_type').value = data.type;
            document.getElementById('editMailingForm').action = `/mailings/${id}`;
            new bootstrap.Modal(document.getElementById('editMailingModal')).show();
        });
}

function deleteMailing(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce mailing ?')) {
        fetch(`/mailings/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}

function editDestinataire(id) {
    fetch(`/rappel-destinataires/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_type').value = data.type;
            document.getElementById('editDestinataireForm').action = `/rappel-destinataires/${id}`;
            new bootstrap.Modal(document.getElementById('editDestinataireModal')).show();
        });
}

function deleteDestinataire(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce destinataire ?')) {
        fetch(`/rappel-destinataires/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}

function viewCheck(checkId) {
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('viewCheckModal'));
    modal.show();

    // Charger les services du check
    fetch(`/checks/${checkId}/services`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('serviceCheckList');
            container.innerHTML = '';
            
            // Parcourir les catégories
            Object.entries(data).forEach(([categoryTitle, serviceChecks]) => {
                // Créer la section de la catégorie
                const categorySection = document.createElement('div');
                categorySection.className = 'mb-4';
                categorySection.innerHTML = `
                    <h6 class="fw-bold mb-3 text-primary">
                        <i class="bi bi-folder me-2"></i>${categoryTitle}
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%;">Service</th>
                                    <th style="width: 15%;">Statut</th>
                                    <th style="width: 25%;">Commentaire</th>
                                    <th style="width: 20%;">Intervenant</th>
                                    <th style="width: 15%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Ajouter les services de la catégorie
                const tbody = categorySection.querySelector('tbody');
                serviceChecks.forEach(serviceCheck => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-service-check-id', serviceCheck.id);
                    // Déterminer le statut affiché (mapping DB -> select)
                    let status = serviceCheck.statut || 'pending';
                    if (status === 'success') status = 'ok';
                    if (status === 'error') status = 'failed';
                    const comment = serviceCheck.observations || '';
                    const intervenant = serviceCheck.intervenant || '';
                    row.innerHTML = `
                        <td>
                            <strong>${serviceCheck.service.title}</strong>
                        </td>
                        <td>
                            <select class="form-select form-select-sm status-select" 
                                    onchange="handleStatusChange(${serviceCheck.id}, this.value, this)">
                                <option value="pending" ${status === 'pending' ? 'selected' : ''}>
                                    En attente
                                </option>
                                <option value="ok" ${status === 'ok' ? 'selected' : ''}>
                                    OK
                                </option>
                                <option value="failed" ${status === 'failed' ? 'selected' : ''}>
                                    NOK
                                </option>
                            </select>
                        </td>
                        <td>
                            <textarea class="form-control form-control-sm comment-input" 
                                    placeholder="Commentaire obligatoire si NOK..."
                                    ${status !== 'failed' ? 'disabled' : ''}>${comment}</textarea>
                            <small class="text-muted comment-help" style="display: none;">
                                <i class="bi bi-info-circle"></i> Commentaire obligatoire pour le statut NOK
                            </small>
                        </td>
                        <td>
                            <select class="form-select form-select-sm intervenant-select" 
                                    ${status !== 'failed' ? 'disabled' : ''}>
                                <option value="">Sélectionner un intervenant</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                        "+(intervenant == {{ $user->id }} ? 'selected' : '')+">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted intervenant-help" style="display: none;">
                                <i class="bi bi-info-circle"></i> Intervenant obligatoire pour le statut NOK
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success btn-sm" 
                                        onclick="validateServiceRow(${serviceCheck.id})" title="Valider cette ligne">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" 
                                        onclick="resetServiceRow(${serviceCheck.id})" title="Réinitialiser">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                container.appendChild(categorySection);
            });

            // Ajouter les boutons d'action dans le footer du modal
            const modalFooter = document.querySelector('#viewCheckModal .modal-footer');
            modalFooter.innerHTML = `
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Fermer
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="saveAllServices(${checkId})">
                            <i class="bi bi-save me-2"></i>Enregistrer tout
                        </button>
                    </div>
                </div>
            `;
        });
}

function handleStatusChange(serviceCheckId, status, selectElement) {
    const row = selectElement.closest('tr');
    const commentInput = row.querySelector('.comment-input');
    const intervenantSelect = row.querySelector('.intervenant-select');
    const commentHelp = row.querySelector('.comment-help');
    const intervenantHelp = row.querySelector('.intervenant-help');

    // Activer/désactiver les champs en fonction du statut
    if (status === 'failed') {
        commentInput.disabled = false;
        intervenantSelect.disabled = false;
        commentInput.classList.add('border-warning');
        intervenantSelect.classList.add('border-warning');
        commentHelp.style.display = 'block';
        intervenantHelp.style.display = 'block';
    } else {
        commentInput.disabled = true;
        intervenantSelect.disabled = true;
        commentInput.classList.remove('border-warning');
        intervenantSelect.classList.remove('border-warning');
        commentHelp.style.display = 'none';
        intervenantHelp.style.display = 'none';
        // Vider les champs si le statut n'est pas NOK
        commentInput.value = '';
        intervenantSelect.value = '';
    }
}

function validateServiceRow(serviceCheckId) {
    const row = document.querySelector(`tr[data-service-check-id="${serviceCheckId}"]`);
    const status = row.querySelector('.status-select').value;
    const comment = row.querySelector('.comment-input').value;
    const intervenantId = row.querySelector('.intervenant-select').value;

    // Mapping JS -> DB
    let statutBD = status;
    if (status === 'ok') {
        statutBD = 'success';
    } else if (status === 'failed') {
        statutBD = 'error';
    }

    // Vérifier si les champs requis sont remplis quand le statut est NOK
    if (statutBD === 'error') {
        if (!comment.trim()) {
            showToast('Erreur', `Le commentaire est obligatoire pour le service "${row.querySelector('td:first-child strong').textContent}"`, 'error');
            row.querySelector('.comment-input').focus();
            return false;
        }
        if (!intervenantId) {
            showToast('Erreur', `L'intervenant est obligatoire pour le service "${row.querySelector('td:first-child strong').textContent}"`, 'error');
            row.querySelector('.intervenant-select').focus();
            return false;
        }
    }

    // Ajouter une classe pour indiquer que la ligne est validée
    row.classList.add('table-success');
    showToast('Succès', 'Ligne validée', 'success');
    return true;
}

function resetServiceRow(serviceCheckId) {
    const row = document.querySelector(`tr[data-service-check-id="${serviceCheckId}"]`);
    const statusSelect = row.querySelector('.status-select');
    const commentInput = row.querySelector('.comment-input');
    const intervenantSelect = row.querySelector('.intervenant-select');

    // Remettre le statut en "En attente"
    statusSelect.value = 'pending';
    
    // Vider et désactiver les champs
    commentInput.value = '';
    intervenantSelect.value = '';
    commentInput.disabled = true;
    intervenantSelect.disabled = true;
    
    // Retirer les classes de validation
    row.classList.remove('table-success');
    commentInput.classList.remove('border-warning');
    intervenantSelect.classList.remove('border-warning');
    
    // Masquer les messages d'aide
    row.querySelector('.comment-help').style.display = 'none';
    row.querySelector('.intervenant-help').style.display = 'none';

    showToast('Info', 'Ligne réinitialisée', 'info');
}

function saveAllServices(checkId) {
    const serviceRows = document.querySelectorAll('tr[data-service-check-id]');
    const serviceChecks = [];

    // Collecter toutes les données
    serviceRows.forEach(row => {
        const serviceCheckId = row.getAttribute('data-service-check-id');
        const status = row.querySelector('.status-select').value;
        const comment = row.querySelector('.comment-input').value;
        const intervenantId = row.querySelector('.intervenant-select').value;

        // Mapping JS -> DB
        let statutBD = status;
        if (status === 'ok') {
            statutBD = 'success';
        } else if (status === 'failed') {
            statutBD = 'error';
        }

        // Vérifier si les champs requis sont remplis quand le statut est NOK
        if (statutBD === 'error') {
            if (!comment.trim()) {
                showToast('Erreur', `Le commentaire est obligatoire pour le service "${row.querySelector('td:first-child strong').textContent}"`, 'error');
                row.querySelector('.comment-input').focus();
                return;
            }
            if (!intervenantId) {
                showToast('Erreur', `L'intervenant est obligatoire pour le service "${row.querySelector('td:first-child strong').textContent}"`, 'error');
                row.querySelector('.intervenant-select').focus();
                return;
            }
        }

        serviceChecks.push({
            id: serviceCheckId,
            status: statutBD,
            observations: statutBD === 'error' ? comment : '',
            intervenant_id: statutBD === 'error' ? intervenantId : null
        });
    });

    // Envoyer toutes les données
    fetch(`/checks/${checkId}/service-checks`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ service_checks: serviceChecks })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'Tous les services ont été enregistrés avec succès', 'success');
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewCheckModal'));
            modal.hide();
            // Rafraîchir la liste des checks sans recharger la page
            if (typeof refreshChecksList === 'function') {
                setTimeout(() => { refreshChecksList(); }, 500);
            }
        } else {
            let message = 'Une erreur est survenue lors de la sauvegarde';
            if (data && data.message) message = data.message;
            showToast('Erreur', message, 'error');
        }
    })
    .catch(async error => {
        let message = 'Une erreur est survenue lors de la sauvegarde';
        if (error && error.response) {
            try {
                const data = await error.response.json();
                if (data && data.message) message = data.message;
            } catch (e) {}
        }
        showToast('Erreur', message, 'error');
    });
}

document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (e) {
        const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const manageBtn = document.getElementById('manageServicesBtn');
    if (manageBtn) {
        manageBtn.onclick = function() {
            showManageServicesSection();
        };
    }
});

function showManageServicesSection() {
    // À compléter : affichage de la section d'ajout/suppression
    alert('Gestion des services à venir !');
}

// Fonction pour rafraîchir la liste des checks
function refreshChecksList() {
    // Recharger la page pour afficher les changements
    window.location.reload();
}
</script>
@endpush

@endsection 