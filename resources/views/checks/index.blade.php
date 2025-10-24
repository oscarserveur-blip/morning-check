@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Vérifications</h2>
                    <div class="flex flex-row-reverse items-center gap-2">
                        <a href="{{ route('checks.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                        <form method="GET" action="" class="flex items-center gap-2 mr-2">
                            <label for="per_page" class="text-sm">Afficher</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                @foreach([5, 10, 15, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                            <span class="text-sm">par page</span>
                            @foreach(request()->except('per_page', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                        </form>
                    </div>
                </div>

                <!-- Filtres et recherche -->
                <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                    <form method="GET" action="{{ route('checks.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Recherche -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       placeholder="Client, créateur, ID, notes..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Filtre par statut -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Tous les statuts</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminé</option>
                                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
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

                            <!-- Tri -->
                            <div>
                                <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                                <select id="sort_by" name="sort_by" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="date_time" {{ request('sort_by', 'date_time') === 'date_time' ? 'selected' : '' }}>Date de vérification</option>
                                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Date de création</option>
                                    <option value="updated_at" {{ request('sort_by') === 'updated_at' ? 'selected' : '' }}>Dernière modification</option>
                                    <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>ID</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Date de début -->
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                                <input type="date" 
                                       id="date_from" 
                                       name="date_from" 
                                       value="{{ request('date_from') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Date de fin -->
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                                <input type="date" 
                                       id="date_to" 
                                       name="date_to" 
                                       value="{{ request('date_to') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Ordre de tri -->
                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Ordre</label>
                                <select id="sort_order" name="sort_order" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>Plus récent en premier</option>
                                    <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Plus ancien en premier</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                <i class="bi bi-search me-2"></i>Filtrer
                            </button>
                            <a href="{{ route('checks.index') }}" class="text-gray-600 hover:text-gray-800">
                                <i class="bi bi-x-circle me-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Statistiques -->
                <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <div class="text-sm text-blue-600">Total</div>
                        <div class="text-2xl font-bold text-blue-800">{{ $checks->total() }}</div>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded-lg">
                        <div class="text-sm text-yellow-600">En attente</div>
                        <div class="text-2xl font-bold text-yellow-800">{{ $checks->getCollection()->where('statut', 'pending')->count() }}</div>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <div class="text-sm text-green-600">Terminés</div>
                        <div class="text-2xl font-bold text-green-800">{{ $checks->getCollection()->where('statut', 'completed')->count() }}</div>
                    </div>
                    <div class="bg-red-50 p-3 rounded-lg">
                        <div class="text-sm text-red-600">Échoués</div>
                        <div class="text-2xl font-bold text-red-800">{{ $checks->getCollection()->where('statut', 'failed')->count() }}</div>
                    </div>
                </div>

                @php
                    $perPage = request('per_page', 10);
                @endphp

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de vérification</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Services vérifiés</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé par</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($checks as $check)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        #{{ $check->id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($check->client->logo)
                                            <img class="h-8 w-8 rounded-full mr-3" src="{{ asset('storage/' . $check->client->logo) }}" alt="{{ $check->client->label }}">
                                        @else
                                            <div class="h-8 w-8 rounded-full bg-gray-200 mr-3 flex items-center justify-center">
                                                <i class="bi bi-building text-gray-500"></i>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">{{ $check->client->label }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $check->date_time->format('d/m/Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $check->date_time->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusConfig = [
                                            'completed' => ['class' => 'bg-green-100 text-green-800', 'text' => 'Terminé', 'icon' => 'bi-check-circle-fill'],
                                            'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'En attente', 'icon' => 'bi-clock-fill'],
                                            'failed' => ['class' => 'bg-red-100 text-red-800', 'text' => 'Échoué', 'icon' => 'bi-x-circle-fill'],
                                            'in_progress' => ['class' => 'bg-blue-100 text-blue-800', 'text' => 'En cours', 'icon' => 'bi-arrow-clockwise']
                                        ];
                                        $status = $statusConfig[$check->statut] ?? ['class' => 'bg-gray-100 text-gray-800', 'text' => $check->statut, 'icon' => 'bi-question-circle-fill'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $status['class'] }}">
                                        <i class="bi {{ $status['icon'] }} mr-1"></i>
                                        {{ $status['text'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $totalServices = $check->serviceChecks->count();
                                        $successServices = $check->serviceChecks->where('statut', 'success')->count();
                                        $warningServices = $check->serviceChecks->where('statut', 'warning')->count();
                                        $errorServices = $check->serviceChecks->where('statut', 'error')->count();
                                    @endphp
                                    @if($totalServices > 0)
                                        <div class="flex items-center">
                                            <div class="mr-2 text-xs">
                                                <span class="text-green-600">{{ $successServices }} ✅</span>
                                                @if($warningServices > 0)
                                                    <span class="text-yellow-600 ml-1">{{ $warningServices }} ⚠️</span>
                                                @endif
                                                @if($errorServices > 0)
                                                    <span class="text-red-600 ml-1">{{ $errorServices }} ❌</span>
                                                @endif
                                            </div>
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($successServices / $totalServices) * 100 }}%"></div>
                                                <div class="bg-yellow-500 h-2 rounded-full -mt-2" style="width: {{ ($warningServices / $totalServices) * 100 }}%"></div>
                                                <div class="bg-red-500 h-2 rounded-full -mt-2" style="width: {{ ($errorServices / $totalServices) * 100 }}%"></div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $totalServices }} service(s)</div>
                                    @else
                                        <span class="text-gray-500 text-sm">Aucun service</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($check->creator)
                                        <div class="flex items-center">
                                            <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                                <i class="bi bi-person text-blue-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm text-gray-900">{{ $check->creator->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-sm">Système</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('checks.show', $check) }}" class="text-indigo-600 hover:text-indigo-900" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('checks.edit', $check) }}" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if($check->client->template)
                                            <a href="{{ route('checks.export', $check) }}" class="text-green-600 hover:text-green-900" title="Exporter">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('checks.destroy', $check) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vérification ?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="bi bi-inbox text-4xl mb-4 block"></i>
                                        <h3 class="text-lg font-medium mb-2">Aucune vérification trouvée</h3>
                                        <p class="text-sm">Aucune vérification ne correspond à vos critères de recherche.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $checks->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 