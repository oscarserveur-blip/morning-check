<form method="POST" action="{{ route('categories.update', $category) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" 
            id="title" name="title" value="{{ old('title', $category->title) }}" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="category_pk" class="form-label">Catégorie parente</label>
        <select class="form-select @error('category_pk') is-invalid @enderror" 
            id="category_pk" name="category_pk">
            <option value="">Aucune catégorie parente</option>
            @foreach($parentCategories as $parent)
                <option value="{{ $parent->id }}" 
                    {{ old('category_pk', $category->category_pk) == $parent->id ? 'selected' : '' }}>
                    {{ $parent->title }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">Laissez vide si cette catégorie n'a pas de parent</small>
        @error('category_pk')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Retour</a>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </div>
</form> 