<form action="{{ route('clients.update', $client) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="label" class="form-label">Nom du client</label>
        <input type="text" class="form-control" id="label" name="label" value="{{ old('label', $client->label) }}" required>
    </div>
    <div class="mb-3">
        <label for="logo" class="form-label">Logo</label>
        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
        @if($client->logo)
            <div class="mt-2">
                <img src="{{ asset('storage/' . $client->logo) }}" alt="Logo actuel" class="rounded" style="max-height: 60px;">
            </div>
        @endif
    </div>
    <div class="mb-3">
        <label for="template_id" class="form-label">Template</label>
        <select class="form-select" id="template_id" name="template_id" required>
            <option value="">Sélectionnez un template</option>
            @foreach($templates as $template)
                <option value="{{ $template->id }}" {{ old('template_id', $client->template_id) == $template->id ? 'selected' : '' }}>{{ $template->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="check_time" class="form-label">Heure de vérification</label>
        <input type="time" class="form-control" id="check_time" name="check_time" value="{{ old('check_time', $client->check_time ? \Carbon\Carbon::parse($client->check_time)->format('H:i') : '09:00') }}" required>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </div>
</form> 