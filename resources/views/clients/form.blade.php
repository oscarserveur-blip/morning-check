@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">{{ isset($client) ? 'Modifier le client' : 'Ajouter un client' }}</h5>
                            <p class="text-muted mb-0">
                                {{ isset($client) ? 'Modifiez les informations du client' : 'Remplissez les informations pour créer un nouveau client' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ isset($client) ? route('clients.update', $client) : route('clients.store') }}" 
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @if(isset($client))
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="label" class="form-label">Nom du client</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-building"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control @error('label') is-invalid @enderror" 
                                           id="label" 
                                           name="label" 
                                           value="{{ old('label', $client->label ?? '') }}"
                                           placeholder="Entrez le nom du client">
                                    @error('label')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="logo" class="form-label">Logo</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-image"></i>
                                    </span>
                                    <input type="file" 
                                           class="form-control @error('logo') is-invalid @enderror" 
                                           id="logo" 
                                           name="logo"
                                           accept="image/*">
                                    @error('logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if(isset($client) && $client->logo)
                                    <div class="mt-2">
                                        <img src="/storage/{{ $client->logo }}" 
                                             alt="Logo actuel" 
                                             class="rounded" 
                                             style="max-height: 100px;">
                                    </div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label for="template_id" class="form-label">Template</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </span>
                                    <select class="form-select @error('template_id') is-invalid @enderror" 
                                            id="template_id" 
                                            name="template_id" required>
                                        <option value="">Sélectionnez un template</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}" 
                                                {{ old('template_id', $client->template_id ?? '') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('template_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="check_time" class="form-label">Heure de vérification</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                    <input type="time" 
                                           class="form-control @error('check_time') is-invalid @enderror" 
                                           id="check_time" 
                                           name="check_time" 
                                           value="{{ old('check_time', isset($client) && $client->check_time ? \Carbon\Carbon::parse($client->check_time)->format('H:i') : '09:00') }}">
                                    @error('check_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('clients.index') }}" class="btn btn-light">
                                <i class="bi bi-x-lg me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>
                                {{ isset($client) ? 'Mettre à jour' : 'Créer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 