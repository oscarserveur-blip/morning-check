<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Checks</h6>
                    <!-- <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCheckModal">
                        <i class="bi bi-plus-lg"></i> Ajouter un check
                    </button> -->
                </div>
            </div>
            <div class="card-body">
                <!-- En-tête avec bouton de création -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Vérifications</h4>
                        <p class="text-muted mb-0">Gérez les vérifications quotidiennes</p>
                    </div>
                    <div class="d-flex gap-2">
                        <!-- Bouton Créer un check automatique -->
                        <button type="button" 
                                class="btn btn-primary" 
                                onclick="createAutoCheck()"
                                {{ $client->services->isEmpty() ? 'disabled' : '' }}>
                            <i class="bi bi-magic me-2"></i>Créer un check automatique
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Rechercher</label>
                        <input type="text" class="form-control" id="searchChecks" placeholder="ID, notes...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="in_progress">En cours</option>
                            <option value="success">Validé</option>
                            <option value="warning">Avertissement</option>
                            <option value="error">Erreur</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date début</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>

                <!-- Tableau des checks -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Services</th>
                                <th>Créé par</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="checksTableBody">
                            @foreach($checks as $check)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#{{ $check->id }}</span>
                                    </td>
                                    <td>{{ $check->date_time->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @php
                                            $statusConfig = [
                                                'success' => ['class' => 'bg-success', 'icon' => 'bi-check-circle-fill', 'text' => 'Validé'],
                                                'pending' => ['class' => 'bg-warning', 'icon' => 'bi-clock-fill', 'text' => 'En attente'],
                                                'in_progress' => ['class' => 'bg-info', 'icon' => 'bi-arrow-clockwise', 'text' => 'En cours'],
                                                'warning' => ['class' => 'bg-warning', 'icon' => 'bi-exclamation-triangle-fill', 'text' => 'Avertissement'],
                                                'error' => ['class' => 'bg-danger', 'icon' => 'bi-x-circle-fill', 'text' => 'Erreur']
                                            ];
                                            $status = $statusConfig[$check->statut] ?? ['class' => 'bg-secondary', 'icon' => 'bi-question-circle-fill', 'text' => 'Inconnu'];
                                        @endphp
                                        <span class="badge {{ $status['class'] }}">
                                            <i class="bi {{ $status['icon'] }} me-1"></i>
                                            {{ $status['text'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $total = $check->serviceChecks->count();
                                            $completed = $check->serviceChecks->where('statut', 'success')->count();
                                            $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" 
                                                     role="progressbar" 
                                                     style="width: {{ $percent }}%" 
                                                     aria-valuenow="{{ $percent }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ $completed }}/{{ $total }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <!-- <div class="avatar avatar-sm me-2">
                                                <div class="avatar-initial rounded-circle bg-light text-dark">
                                                    {{ substr($check->creator->name ?? 'U', 0, 1) }}
                                                </div>
                                            </div> -->
                                            <div class="ms-2">
                                                <div class="small fw-medium">{{ $check->creator->name ?? 'Inconnu' }}</div>
                                                <!-- <div class="small text-muted">{{ $check->created_at->format('d/m/Y H:i') }}</div> -->
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <!-- Bouton Voir/Éditer (modal) -->
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openCheckModal({{ $check->id }})">
                                                <i class="bi bi-pencil me-1"></i>Gérer
                                            </button>
                                            <!-- Bouton Télécharger -->
                                            <button type="button" 
                                                    onclick="downloadCheck({{ $check->id }})"
                                                    class="btn btn-sm btn-outline-success"
                                                    {{ in_array($check->statut, ['pending', 'in_progress']) ? 'disabled' : '' }}>
                                                <i class="bi bi-download me-1"></i>Télécharger
                                            </button>
                                            <!-- Bouton Envoyer -->
                                            <button type="button" 
                                                    onclick="sendCheck({{ $check->id }})"
                                                    class="btn btn-sm btn-outline-info"
                                                    {{ in_array($check->statut, ['pending', 'in_progress']) ? 'disabled' : '' }}>
                                                <i class="bi bi-send me-1"></i>Envoyer
                                            </button>
                                            <!-- Bouton Supprimer -->
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCheck({{ $check->id }})">
                                                <i class="bi bi-trash me-1"></i>Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Affichage de {{ $checks->firstItem() ?? 0 }} à {{ $checks->lastItem() ?? 0 }} sur {{ $checks->total() }} checks
                    </div>
                    {{ $checks->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@include('clients.partials.modals.check')
@include('clients.partials.toast')

<!-- Modal d'avertissement export -->
<div class="modal fade" id="exportWarningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export impossible</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Impossible de télécharger ce check : tous les services doivent être validés (aucun en attente, erreur ou avertissement).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de gestion des services d'un check (ancien modal complet) -->
<div class="modal fade" id="viewCheckModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestion des services du check</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3 gap-2">
                    <button type="button" class="btn btn-outline-success" id="setAllOkBtn">
                        <i class="bi bi-check-circle me-1"></i> Tout mettre à OK
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="setAllNokBtn">
                        <i class="bi bi-x-circle me-1"></i> Tout mettre à NOK
                    </button>
                    <div id="globalCommentContainer" class="ms-3" style="display:none; flex:1;">
                        <input type="text" class="form-control" id="globalCommentInput" placeholder="Commentaire global pour NOK...">
                    </div>
                </div>
                <div id="serviceCheckList"></div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Fermer
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="saveAllServicesBtn">
                            <i class="bi bi-save me-2"></i>Enregistrer tout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Générer dynamiquement les options intervenants côté JS
window.intervenantsOptionsHtml = `@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach`;

function createAutoCheck() {
    // Afficher un indicateur de chargement
    Swal.fire({
        title: 'Création du check en cours...',
        html: 'Veuillez patienter pendant la création automatique du check.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Appel AJAX pour créer le check
    fetch(`/clients/{{ $client->id }}/checks/auto`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Check créé',
                text: 'Le check a été créé avec succès.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Rafraîchir la page pour afficher le nouveau check
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Erreur lors de la création du check');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: error.message
        });
    });
}

// Fonction pour rafraîchir la liste des checks
function refreshChecksList() {
    // Recharger la page pour afficher les changements
    window.location.reload();
}

function downloadCheck(checkId) {
    // Vérifier d'abord l'état du check
    fetch(`/checks/${checkId}/status`)
    .then(response => response.json())
    .then(data => {
        if (data.can_download) {
            // Si tout est validé, télécharger
            window.location.href = `/checks/${checkId}/export`;
        } else {
            // Sinon, afficher un message d'erreur
            Swal.fire({
                icon: 'warning',
                title: 'Services non validés',
                text: 'Tous les services doivent être validés avant de pouvoir télécharger le rapport.',
                confirmButtonText: 'Compris'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Impossible de vérifier l\'état du check.'
        });
    });
}

function sendCheck(checkId) {
    // Vérifier d'abord l'état du check
    fetch(`/checks/${checkId}/status`)
    .then(response => response.json())
    .then(data => {
        if (data.can_download) {
            Swal.fire({
                title: 'Envoyer le rapport ?',
                text: 'Le rapport sera envoyé aux destinataires du client.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Envoyer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    fetch(`/checks/${checkId}/send`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async res => {
                        let body = null;
                        try { body = await res.json(); } catch (_) {}
                        if (!body) {
                            // try text
                            try { const txt = await res.text(); body = { success: false, message: txt }; } catch(_) {}
                        }
                        return { ok: res.ok, status: res.status, body };
                    })
                    .then(({ ok, body }) => {
                        if (ok && body && body.success) {
                            Swal.fire({ icon: 'success', title: 'Email envoyé', text: body.message || 'Le rapport a été envoyé.' });
                        } else {
                            const message = (body && body.message) || 'Échec de l\'envoi de l\'email.';
                            Swal.fire({ icon: 'error', title: 'Erreur', text: message });
                        }
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible d\'envoyer l\'email.' });
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Services non validés',
                text: 'Tous les services doivent être validés avant de pouvoir envoyer le rapport.',
                confirmButtonText: 'Compris'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Impossible de vérifier l\'état du check.'
        });
    });
}

function deleteCheck(checkId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteCheckModal'));
    const form = document.getElementById('deleteCheckForm');
    form.action = `/checks/${checkId}`;
    modal.show();
}

function openCheckModal(checkId) {
    const modal = new bootstrap.Modal(document.getElementById('viewCheckModal'));
    document.getElementById('serviceCheckList').innerHTML = '<div class="text-center py-5"><div class="spinner-border"></div></div>';
    fetch(`/checks/${checkId}/services`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('serviceCheckList');
            container.innerHTML = '';
            Object.entries(data).forEach(([categoryTitle, serviceChecks]) => {
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
                            <tbody></tbody>
                        </table>
                    </div>
                `;
                const tbody = categorySection.querySelector('tbody');
                serviceChecks.forEach(serviceCheck => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-service-check-id', serviceCheck.id);
                    let status = serviceCheck.statut || 'pending';
                    if (status === 'success') status = 'ok';
                    if (status === 'error') status = 'failed';
                    const comment = serviceCheck.observations || '';
                    const intervenant = serviceCheck.intervenant || '';
                    row.innerHTML = `
                        <td><strong>${serviceCheck.service.title}</strong></td>
                        <td>
                            <select class="form-select form-select-sm status-select" onchange="handleStatusChange(${serviceCheck.id}, this.value, this)">
                                <option value="pending" ${status === 'pending' ? 'selected' : ''}>En attente</option>
                                <option value="ok" ${status === 'ok' ? 'selected' : ''}>OK</option>
                                <option value="failed" ${status === 'failed' ? 'selected' : ''}>NOK</option>
                            </select>
                        </td>
                        <td>
                            <textarea class="form-control form-control-sm comment-input" placeholder="Commentaire obligatoire si NOK..." ${status !== 'failed' ? 'disabled' : ''}>${comment}</textarea>
                            <small class="text-muted comment-help" style="display: none;">
                                <i class="bi bi-info-circle"></i> Commentaire obligatoire pour le statut NOK
                            </small>
                        </td>
                        <td>
                            <select class="form-select form-select-sm intervenant-select" ${status !== 'failed' ? 'disabled' : ''}>
                                <option value="">Sélectionner un intervenant</option>
                                ${window.intervenantsOptionsHtml}
                            </select>
                            <small class="text-muted intervenant-help" style="display: none;">
                                <i class="bi bi-info-circle"></i> Intervenant obligatoire pour le statut NOK
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="validateServiceRow(${serviceCheck.id})" title="Valider cette ligne">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="resetServiceRow(${serviceCheck.id})" title="Réinitialiser">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    // Sélectionner l'intervenant si déjà défini
                    setTimeout(() => {
                        const select = row.querySelector('.intervenant-select');
                        if (select && intervenant !== null && intervenant !== undefined && intervenant !== '') {
                            select.value = String(intervenant);
                        }
                    }, 0);
                    tbody.appendChild(row);
                });
                container.appendChild(categorySection);
            });
        });
    // Associer le bouton d'enregistrement
    setTimeout(() => {
        const saveBtn = document.getElementById('saveAllServicesBtn');
        if (saveBtn) {
            saveBtn.onclick = function() { saveAllServices(checkId); };
        }
    }, 500);
    modal.show();
}

function handleStatusChange(serviceCheckId, status, selectElement) {
    const row = selectElement.closest('tr');
    const commentInput = row.querySelector('.comment-input');
    const intervenantSelect = row.querySelector('.intervenant-select');
    const commentHelp = row.querySelector('.comment-help');
    const intervenantHelp = row.querySelector('.intervenant-help');
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
        commentInput.value = '';
        intervenantSelect.value = '';
        commentHelp.style.display = 'none';
        intervenantHelp.style.display = 'none';
    }
}

function validateServiceRow(serviceCheckId) {
    const row = document.querySelector(`tr[data-service-check-id="${serviceCheckId}"]`);
    const status = row.querySelector('.status-select').value;
    const comment = row.querySelector('.comment-input').value;
    const intervenantId = row.querySelector('.intervenant-select').value;
    let statutBD = status;
    if (status === 'ok') {
        statutBD = 'success';
    } else if (status === 'failed') {
        statutBD = 'error';
    }
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
    row.classList.add('table-success');
    showToast('Succès', 'Ligne validée', 'success');
    return true;
}

function resetServiceRow(serviceCheckId) {
    const row = document.querySelector(`tr[data-service-check-id="${serviceCheckId}"]`);
    const statusSelect = row.querySelector('.status-select');
    const commentInput = row.querySelector('.comment-input');
    const intervenantSelect = row.querySelector('.intervenant-select');
    statusSelect.value = 'pending';
    commentInput.value = '';
    intervenantSelect.value = '';
    commentInput.disabled = true;
    intervenantSelect.disabled = true;
    row.classList.remove('table-success');
    commentInput.classList.remove('border-warning');
    intervenantSelect.classList.remove('border-warning');
    row.querySelector('.comment-help').style.display = 'none';
    row.querySelector('.intervenant-help').style.display = 'none';
    showToast('Info', 'Ligne réinitialisée', 'info');
}

function saveAllServices(checkId) {
    const serviceRows = document.querySelectorAll('tr[data-service-check-id]');
    const serviceChecks = [];
    serviceRows.forEach(row => {
        const serviceCheckId = row.getAttribute('data-service-check-id');
        const status = row.querySelector('.status-select').value;
        const comment = row.querySelector('.comment-input').value;
        const intervenantId = row.querySelector('.intervenant-select').value;
        let statutBD = status;
        if (status === 'ok') {
            statutBD = 'success';
        } else if (status === 'failed') {
            statutBD = 'error';
        }
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewCheckModal'));
            modal.hide();
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

// Filtrage dynamique
document.getElementById('searchChecks').addEventListener('input', filterChecks);
document.getElementById('filterStatus').addEventListener('change', filterChecks);
document.getElementById('startDate').addEventListener('change', filterChecks);
document.getElementById('endDate').addEventListener('change', filterChecks);

function filterChecks() {
    const search = document.getElementById('searchChecks').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    // Construire l'URL avec les paramètres de filtrage
    const url = new URL(window.location.href);
    url.searchParams.set('search', search);
    if (status) url.searchParams.set('status', status);
    if (startDate) url.searchParams.set('start_date', startDate);
    if (endDate) url.searchParams.set('end_date', endDate);

    // Recharger la page avec les filtres
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const setAllOkBtn = document.getElementById('setAllOkBtn');
    const setAllNokBtn = document.getElementById('setAllNokBtn');
    const globalCommentContainer = document.getElementById('globalCommentContainer');
    const globalCommentInput = document.getElementById('globalCommentInput');

    if (setAllOkBtn) {
        setAllOkBtn.onclick = function() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = 'ok';
                select.dispatchEvent(new Event('change'));
            });
            if(globalCommentContainer) globalCommentContainer.style.display = 'none';
        };
    }
    if (setAllNokBtn) {
        setAllNokBtn.onclick = function() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = 'failed';
                select.dispatchEvent(new Event('change'));
            });
            if(globalCommentContainer) globalCommentContainer.style.display = 'flex';
        };
    }
    if (globalCommentInput) {
        globalCommentInput.oninput = function() {
            document.querySelectorAll('.comment-input').forEach(input => {
                input.value = globalCommentInput.value;
            });
        };
    }
});
</script>
@endpush