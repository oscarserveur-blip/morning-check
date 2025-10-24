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
                                            <img src="{{ asset('storage/' . $template->header_logo) }}" alt="Logo actuel" style="max-height:60px;">
                                        </div>
                                    @elseif(session('imported_header_logo'))
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . session('imported_header_logo')) }}" alt="Logo importé" style="max-height:60px;">
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
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="ok_color" class="form-label">Couleur OK</label>
                                                <input type="color" 
                                                    class="form-control form-control-color" 
                                                    id="ok_color" 
                                                    name="ok_color" 
                                                    value="#{{ old('ok_color', isset($template->config['ok_color']) ? $template->config['ok_color'] : '00B050') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="warning_color" class="form-label">Couleur Avertissement</label>
                                                <input type="color" 
                                                    class="form-control form-control-color" 
                                                    id="warning_color" 
                                                    name="warning_color" 
                                                    value="#{{ old('warning_color', isset($template->config['warning_color']) ? $template->config['warning_color'] : 'FFC000') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
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
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="excel_template" class="form-label">Importer un fichier Excel (.xlsx)</label>
                                    <input type="file" name="excel_template" id="excel_template" class="form-control" accept=".xlsx">
                                    <small class="form-text text-muted">Vous pouvez importer un fichier Excel comme base de votre template.</small>
                                </div>
                            </div>
                            @if(session('excel_preview'))
                                <div class="mb-4 p-3 border rounded bg-light shadow-sm">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2" style="font-size:1.5rem;">📄</span>
                                        <h5 class="mb-0">Aperçu du fichier Excel importé</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle mb-0">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="width:40px;">#</th>
                                                    @php $maxCols = 0; foreach(session('excel_preview') as $row) { $maxCols = max($maxCols, count($row)); } @endphp
                                                    @for($col=1; $col<=$maxCols; $col++)
                                                        <th>Col {{ $col }}</th>
                                                    @endfor
                                                </tr>
                                            </thead>
                                            <tbody>
                                                    @foreach(session('excel_preview') as $i => $row)
                                                        <tr>
                                                            <td class="bg-light text-muted">{{ $i+1 }}</td>
                                                            @for($col=0; $col<$maxCols; $col++)
                                                                <td>{{ $row[$col] ?? '' }}</td>
                                                            @endfor
                                                        </tr>
                                                    @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="form-text text-muted">Seules les 10 premières lignes sont affichées. Vérifiez la structure avant de continuer.</small>
                                </div>
                            @endif
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
@endsection 