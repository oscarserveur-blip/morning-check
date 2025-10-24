@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Services</h2>
                    <a href="{{ route('services.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Nouveau Service
                    </a>
                </div>

                <!-- Filtres et recherche -->
                <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                    <form method="GET" action="{{ route('services.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Recherche -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       placeholder="Service, catégorie, client..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Filtre par statut -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Tous les statuts</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actif</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactif</option>
                                </select>
                            </div>

                            <!-- Filtre par client -->
                            <div>
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select id="client_id" name="client_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Tous les clients</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtre par catégorie -->
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                                <select id="category_id" name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Toutes les catégories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->title }} ({{ $category->client->label }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Tri -->
                            <div>
                                <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                                <select id="sort_by" name="sort_by" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Date de création</option>
                                    <option value="updated_at" {{ request('sort_by') === 'updated_at' ? 'selected' : '' }}>Dernière modification</option>
                                    <option value="title" {{ request('sort_by') === 'title' ? 'selected' : '' }}>Nom du service</option>
                                    <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>ID</option>
                                </select>
                            </div>

                            <!-- Ordre de tri -->
                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Ordre</label>
                                <select id="sort_order" name="sort_order" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>Plus récent en premier</option>
                                    <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Plus ancien en premier</option>
                                </select>
                            </div>

                            <!-- Actions -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                                <div class="flex space-x-2">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        <i class="bi bi-search me-2"></i>Filtrer
                                    </button>
                                    <a href="{{ route('services.index') }}" class="text-gray-600 hover:text-gray-800 font-bold py-2 px-4 rounded border border-gray-300">
                                        <i class="bi bi-x-circle me-2"></i>Réinitialiser
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Statistiques -->
                <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <div class="text-sm text-blue-600">Total</div>
                        <div class="text-2xl font-bold text-blue-800">{{ $services->total() }}</div>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <div class="text-sm text-green-600">Actifs</div>
                        <div class="text-2xl font-bold text-green-800">{{ $services->getCollection()->where('status', true)->count() }}</div>
                    </div>
                    <div class="bg-red-50 p-3 rounded-lg">
                        <div class="text-sm text-red-600">Inactifs</div>
                        <div class="text-2xl font-bold text-red-800">{{ $services->getCollection()->where('status', false)->count() }}</div>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-lg">
                        <div class="text-sm text-purple-600">Clients</div>
                        <div class="text-2xl font-bold text-purple-800">{{ $services->getCollection()->groupBy('category.client_id')->count() }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <form method="GET" action="" class="flex items-center gap-2">
                        <label for="per_page" class="text-sm">Afficher</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            @foreach([5, 10, 15, 20, 50, 100] as $size)
                                <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span class="text-sm">par page</span>
                        @foreach(request()->except('per_page', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé par</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date création</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($services as $service)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        #{{ $service->id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $service->title }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $service->category->title }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($service->category->client->logo)
                                            <img class="h-6 w-6 rounded-full mr-2" src="{{ asset('storage/' . $service->category->client->logo) }}" alt="{{ $service->category->client->label }}">
                                        @else
                                            <div class="h-6 w-6 rounded-full bg-gray-200 mr-2 flex items-center justify-center">
                                                <i class="bi bi-building text-gray-500 text-xs"></i>
                                            </div>
                                        @endif
                                        <span class="text-sm text-gray-900">{{ $service->category->client->label }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($service->status)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="bi bi-check-circle-fill mr-1"></i>Actif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="bi bi-x-circle-fill mr-1"></i>Inactif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($service->creator)
                                        <div class="flex items-center">
                                            <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                                <i class="bi bi-person text-blue-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm text-gray-900">{{ $service->creator->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-sm">Système</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $service->created_at->format('d/m/Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $service->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('services.edit', $service) }}" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('services.destroy', $service) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="bi bi-inbox text-4xl mb-4 block"></i>
                                        <h3 class="text-lg font-medium mb-2">Aucun service trouvé</h3>
                                        <p class="text-sm">Aucun service ne correspond à vos critères de recherche.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $services->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 