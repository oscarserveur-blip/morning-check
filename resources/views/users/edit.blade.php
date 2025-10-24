@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Éditer l'utilisateur</h2>
    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $user->name) }}">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required value="{{ old('email', $user->email) }}">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Rôle</label>
            <select class="form-select" id="role" name="role" required onchange="toggleClientsSelect()">
                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="gestionnaire" {{ $user->role === 'gestionnaire' ? 'selected' : '' }}>Gestionnaire</option>
            </select>
        </div>
        <div class="mb-3" id="clientsSelectContainer" style="display:none;">
            <label for="clients" class="form-label">Clients à gérer</label>
            <select class="form-select" id="clients" name="clients[]" multiple>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ $user->clients->contains($client->id) ? 'selected' : '' }}>{{ $client->label }}</option>
                @endforeach
            </select>
            <small class="text-muted">Maintenez Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs clients.</small>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>
<script>
function toggleClientsSelect() {
    const role = document.getElementById('role').value;
    document.getElementById('clientsSelectContainer').style.display = (role === 'gestionnaire') ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    toggleClientsSelect();
});
</script>
@endsection 