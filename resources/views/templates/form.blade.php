@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">{{ isset($template) ? 'Modifier le Template' : 'Nouveau Template' }}</h5>
                            <p class="text-muted mb-0">{{ isset($template) ? 'Modifiez les informations du template' : 'Créez un nouveau template' }}</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($template))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $template->name ?? '') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="">Sélectionnez un type</option>
                                        <option value="excel" {{ old('type', $template->type ?? '') == 'excel' ? 'selected' : '' }}>Excel</option>
                                        <option value="pdf" {{ old('type', $template->type ?? '') == 'pdf' ? 'selected' : '' }}>PDF</option>
                                        <option value="png" {{ old('type', $template->type ?? '') == 'png' ? 'selected' : '' }}>PNG (image)</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $template->description ?? '') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Sélection des clients -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Clients associés</label>
                                    <div class="row">
                                        @foreach($clients as $client)
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="client_ids[]" 
                                                           value="{{ $client->id }}" 
                                                           id="client_{{ $client->id }}"
                                                           {{ (isset($template) && $template->clients->contains($client->id)) || in_array($client->id, old('client_ids', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="client_{{ $client->id }}">
                                                        <strong>{{ $client->label }}</strong>
                                                        @if($client->template)
                                                            <small class="text-muted d-block">Template: {{ $client->template->name }}</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('client_ids')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Sélectionnez les clients qui utiliseront ce template. 
                                        @if(auth()->user()->isGestionnaire())
                                            Vous ne pouvez sélectionner que vos clients assignés.
                                        @endif
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="header_logo" class="form-label">Importer un logo (image)</label>
                                    <input type="file" name="header_logo" id="header_logo" class="form-control" accept="image/*">
                                    <small class="form-text text-muted">Vous pouvez importer une image (logo) à utiliser dans le header du template.</small>
                                    @if(isset($template) && $template->header_logo)
                                        <div class="mt-2">
                                            <img src="/storage/{{ $template->header_logo }}" alt="Logo actuel" style="max-height:60px;">
                                        </div>
                                    @elseif(session('imported_header_logo'))
                                        <div class="mt-2">
                                            <img src="/storage/{{ session('imported_header_logo') }}" alt="Logo importé" style="max-height:60px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="header_title" class="form-label">Titre du header</label>
                                    <input type="text" class="form-control @error('header_title') is-invalid @enderror" id="header_title" name="header_title" value="{{ old('header_title', session('imported_header_title') ?? ($template->header_title ?? '')) }}">
                                    @error('header_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="header_color" class="form-label">Couleur du header</label>
                                    <input type="color" 
                                        class="form-control form-control-color @error('header_color') is-invalid @enderror" 
                                        id="header_color" 
                                        name="header_color" 
                                        value="{{ old('header_color', $template->header_color ?? '#FF0000') }}">
                                    @error('header_color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="footer_color" class="form-label">Couleur du footer</label>
                                    <input type="color" 
                                        class="form-control form-control-color @error('footer_color') is-invalid @enderror" 
                                        id="footer_color" 
                                        name="footer_color" 
                                        value="{{ old('footer_color', $template->footer_color ?? '#F8F8F8') }}">
                                    @error('footer_color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="footer_text" class="form-label">Texte du footer</label>
                                    <input type="text" class="form-control @error('footer_text') is-invalid @enderror" id="footer_text" name="footer_text" value="{{ old('footer_text', session('imported_footer_text') ?? ($template->footer_text ?? '')) }}">
                                    @error('footer_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <!-- Configuration des sections (catégories des clients) -->
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-3">📋 Sections (catégories des clients)</h6>
                                    <p class="text-muted mb-3">Les sections sont basées sur les catégories des clients sélectionnés.</p>

                                    <div id="sections-container" class="row g-2">
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">
                                                Sélectionnez un ou plusieurs clients ci-dessus pour charger leurs catégories.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Champs cachés pour la configuration JSON -->
                                    <input type="hidden" name="section_config" id="section_config_hidden">
                                    <input type="hidden" name="config" id="config_hidden">
                                </div>
                            </div>
                            
                            <!-- Options d'affichage -->
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-3">🎨 Options d'Affichage</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Style des statuts</label>
                                                <select class="form-select" name="status_style" id="status_style">
                                                    <option value="simple">Simple (OK/ERREUR)</option>
                                                    <option value="detailed">Détaillé (OK/ATTENTION/ERREUR)</option>
                                                    <option value="colors">Avec couleurs</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Format de date</label>
                                                <select class="form-select" name="date_format" id="date_format">
                                                    <option value="french">Français (lundi 01/09/2025)</option>
                                                    <option value="iso">ISO (2025-09-01)</option>
                                                    <option value="short">Court (01/09/2025)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="show_timestamp" id="show_timestamp" checked>
                                                    <label class="form-check-label" for="show_timestamp">
                                                        Afficher l'heure de génération
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="show_contact_info" id="show_contact_info" checked>
                                                    <label class="form-check-label" for="show_contact_info">
                                                        Afficher les informations de contact
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Couleurs des statuts -->
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-3">🎨 Couleurs des statuts</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="ok_color" class="form-label">Couleur OK</label>
                                                <input type="color" 
                                                    class="form-control form-control-color" 
                                                    id="ok_color" 
                                                    name="ok_color" 
                                                    value="#{{ old('ok_color', isset($template->config['ok_color']) ? $template->config['ok_color'] : '00B050') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nok_color" class="form-label">Couleur NOK</label>
                                                <input type="color" 
                                                    class="form-control form-control-color" 
                                                    id="nok_color" 
                                                    name="nok_color" 
                                                    value="#{{ old('nok_color', isset($template->config['nok_color']) ? $template->config['nok_color'] : 'FF0000') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuration des colonnes d'export -->
                        <div class="col-12 mt-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-table me-2"></i>Colonnes à afficher dans les exports</h6>
                                    <small class="text-muted">Cochez les colonnes que vous souhaitez voir dans les fichiers Excel, PDF et CSV générés</small>
                                </div>
                                <div class="card-body">
                                    @php
                                        // Colonnes disponibles avec leurs libellés par défaut
                                        $availableColumns = [
                                            'description' => ['label' => 'Description', 'icon' => 'bi-file-text', 'description' => 'Nom du service'],
                                            'category_full_path' => ['label' => 'Catégorie complète', 'icon' => 'bi-folder', 'description' => 'Chemin complet de la catégorie'],
                                            'statut' => ['label' => 'État', 'icon' => 'bi-check-circle', 'description' => 'Statut du service (OK/NOK)'],
                                            'expiration_date' => ['label' => 'Date d\'expiration', 'icon' => 'bi-calendar-x', 'description' => 'Date d\'expiration si applicable'],
                                            'notes' => ['label' => 'Notes', 'icon' => 'bi-sticky', 'description' => 'Notes générales'],
                                            'observations' => ['label' => 'Observations', 'icon' => 'bi-eye', 'description' => 'Observations détaillées'],
                                            'intervenant' => ['label' => 'Intervenant', 'icon' => 'bi-person', 'description' => 'Personne assignée'],
                                            'created_at' => ['label' => 'Date de vérification', 'icon' => 'bi-clock', 'description' => 'Date et heure de la vérification'],
                                        ];
                                        
                                        // Récupérer la configuration actuelle
                                        $currentColumns = old('export_columns', isset($template) && $template->export_columns ? $template->export_columns : [
                                            ['field' => 'description', 'label' => 'Description'],
                                            ['field' => 'category_full_path', 'label' => 'Catégorie complète'],
                                            ['field' => 'statut', 'label' => 'Etat'],
                                            ['field' => 'expiration_date', 'label' => 'Date d\'expiration'],
                                            ['field' => 'notes', 'label' => 'Notes'],
                                        ]);
                                        
                                        // Créer un tableau associatif pour faciliter la vérification
                                        $enabledColumns = [];
                                        $columnLabels = [];
                                        foreach ($currentColumns as $col) {
                                            $enabledColumns[$col['field']] = true;
                                            $columnLabels[$col['field']] = $col['label'];
                                        }
                                    @endphp
                                    
                                    <div class="row">
                                        @foreach($availableColumns as $field => $info)
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100 border">
                                                    <div class="card-body p-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input column-checkbox" 
                                                                   type="checkbox" 
                                                                   name="export_columns_enabled[]" 
                                                                   value="{{ $field }}"
                                                                   id="col_{{ $field }}"
                                                                   {{ isset($enabledColumns[$field]) ? 'checked' : '' }}
                                                                   data-field="{{ $field }}">
                                                            <label class="form-check-label w-100" for="col_{{ $field }}">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bi {{ $info['icon'] }} me-2 text-primary"></i>
                                                                    <strong>{{ $info['label'] }}</strong>
                                                                </div>
                                                                <small class="text-muted d-block ms-4">{{ $info['description'] }}</small>
                                                            </label>
                                                        </div>
                                                        
                                                        <div class="mt-2 column-label-input" 
                                                             style="display: {{ isset($enabledColumns[$field]) ? 'block' : 'none' }};">
                                                            <label class="form-label small">Libellé personnalisé (optionnel)</label>
                                                            <input type="text" 
                                                                   class="form-control form-control-sm" 
                                                                   name="export_columns_labels[{{ $field }}]"
                                                                   value="{{ $columnLabels[$field] ?? $info['label'] }}"
                                                                   placeholder="{{ $info['label'] }}">
                                                            <input type="hidden" 
                                                                   name="export_columns_order[]" 
                                                                   value="{{ $field }}"
                                                                   class="column-order-input">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <div class="alert alert-info mt-3 mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Astuce :</strong> Les colonnes seront affichées dans l'ordre où vous les cochez. 
                                        Vous pouvez personnaliser le nom de chaque colonne si vous le souhaitez.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('templates.index') }}" class="btn btn-light me-2">Annuler</a>
                            <button type="button" class="btn btn-outline-info me-2" data-bs-toggle="modal" data-bs-target="#templatePreview">
                                <i class="bi bi-eye"></i> Aperçu
                            </button>
                            <button type="submit" class="btn btn-primary">{{ isset($template) ? 'Mettre à jour' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aperçu du template -->
<div class="modal fade" id="templatePreview" tabindex="-1" aria-labelledby="templatePreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templatePreviewLabel">Aperçu du Bulletin de Santé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="template-preview-content" class="border rounded p-4 bg-white">
                    <!-- L'aperçu sera généré ici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updatePreview()">Actualiser l'aperçu</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/templates/simple-config.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Afficher/masquer les champs de libellé personnalisé
        document.querySelectorAll('.column-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const labelInput = this.closest('.card-body').querySelector('.column-label-input');
                const orderInput = this.closest('.card-body').querySelector('.column-order-input');
                
                if (this.checked) {
                    labelInput.style.display = 'block';
                    if (orderInput) {
                        orderInput.disabled = false;
                    }
                } else {
                    labelInput.style.display = 'none';
                    if (orderInput) {
                        orderInput.disabled = true;
                    }
                }
            });
            
            // Déclencher l'événement au chargement pour afficher les champs déjà cochés
            if (checkbox.checked) {
                checkbox.dispatchEvent(new Event('change'));
            }
        });
        
        // Gérer l'ordre des colonnes (drag & drop simple avec réorganisation visuelle)
        let selectedColumns = [];
        document.querySelectorAll('.column-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateColumnOrder();
            });
        });
        
        function updateColumnOrder() {
            // Mettre à jour l'ordre des colonnes selon l'ordre de sélection
            const checkedBoxes = Array.from(document.querySelectorAll('.column-checkbox:checked'));
            checkedBoxes.forEach(function(checkbox, index) {
                const orderInput = checkbox.closest('.card-body').querySelector('.column-order-input');
                if (orderInput) {
                    orderInput.value = index;
                }
            });
        }
        
        // Initialiser l'ordre au chargement
        updateColumnOrder();
    });
    </script>
@endsection 