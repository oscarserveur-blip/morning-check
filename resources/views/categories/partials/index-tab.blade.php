<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Catégorie parente</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
                <tr>
                    <td>{{ $category->title }}</td>
                    <td>{{ $category->parent ? $category->parent->title : 'Aucune' }}</td>
                    <td>
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-primary">Modifier</a>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div> 