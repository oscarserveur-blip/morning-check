@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête avec infos du check -->
        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <!-- Infos du check -->
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <h1 class="text-2xl font-bold text-gray-900">Check #{{ $check->id }}</h1>
                            <!-- Badge de statut -->
                            @php
                                $statusConfig = [
                                    'success' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'bi-check-circle-fill'],
                                    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'bi-clock-fill'],
                                    'error' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'bi-x-circle-fill'],
                                    'warning' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'icon' => 'bi-exclamation-circle-fill'],
                                    'in_progress' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'bi-arrow-clockwise']
                                ];
                                $status = $statusConfig[$check->statut] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'bi-question-circle-fill'];
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                                <i class="bi {{ $status['icon'] }} mr-2"></i>
                                {{ ucfirst($check->statut) }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><i class="bi bi-calendar3 mr-2"></i>{{ $check->date_time->format('d/m/Y H:i') }}</p>
                            <p><i class="bi bi-person mr-2"></i>Créé par {{ $check->creator->name }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Bouton Télécharger -->
                        <div x-data="{ showModal: false }">
                            <button 
                                @click="showModal = true"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm transition-colors"
                                {{ $check->statut !== 'success' ? 'disabled' : '' }}
                            >
                                <i class="bi bi-download mr-2"></i>
                                Télécharger
                            </button>

                            <!-- Modal de confirmation -->
                            <div x-show="showModal" 
                                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                            >
                                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" 
                                     @click.away="showModal = false">
                                    <div class="p-6">
                                        @if($check->statut === 'success')
                                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                                <i class="bi bi-check-circle-fill text-green-500 mr-2"></i>
                                                Prêt pour le téléchargement
                                            </h3>
                                            <p class="text-gray-600 mb-6">
                                                Tous les services sont validés. Vous pouvez télécharger le rapport.
                                            </p>
                                            <div class="flex justify-end gap-4">
                                                <button @click="showModal = false" 
                                                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                                                    Annuler
                                                </button>
                                                <a href="{{ route('checks.export', $check) }}" 
                                                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                                                    Télécharger
                                                </a>
                                            </div>
                                        @else
                                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                                <i class="bi bi-exclamation-triangle-fill text-yellow-500 mr-2"></i>
                                                Services non validés
                                            </h3>
                                            <p class="text-gray-600 mb-6">
                                                Certains services ne sont pas encore validés. Veuillez valider tous les services avant de télécharger le rapport.
                                            </p>
                                            <div class="flex justify-end">
                                                <button @click="showModal = false" 
                                                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                                                    Fermer
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des services par catégorie -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-6">
                <!-- Barre de progression globale -->
                <div class="mb-6">
                    @php
                        $total = $check->serviceChecks->count();
                        $completed = $check->serviceChecks->where('statut', 'success')->count();
                        $percent = $total > 0 ? ($completed / $total) * 100 : 0;
                    @endphp
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700">Progression globale</h3>
                        <span class="text-sm text-gray-600">{{ $completed }}/{{ $total }} services validés</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>

                <!-- Services groupés par catégorie -->
                <div x-data="{ activeCategory: null }">
                    @foreach($check->serviceChecks->groupBy('service.category.title') as $category => $services)
                        <div class="border rounded-lg mb-4 last:mb-0">
                            <!-- En-tête de catégorie -->
                            <button @click="activeCategory = activeCategory === '{{ $category }}' ? null : '{{ $category }}'"
                                    class="w-full px-4 py-3 flex items-center justify-between bg-gray-50 hover:bg-gray-100 rounded-t-lg transition-colors">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $category }}</h3>
                                    @php
                                        $categoryCompleted = $services->where('statut', 'success')->count();
                                        $categoryTotal = $services->count();
                                        $categoryPercent = ($categoryCompleted / $categoryTotal) * 100;
                                    @endphp
                                    <span class="ml-4 text-sm text-gray-600">
                                        {{ $categoryCompleted }}/{{ $categoryTotal }} validés
                                    </span>
                                </div>
                                <i class="bi" :class="activeCategory === '{{ $category }}' ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </button>

                            <!-- Liste des services de la catégorie -->
                            <div x-show="activeCategory === '{{ $category }}'"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                                 class="p-4 border-t">
                                <div class="space-y-4">
                                    @foreach($services as $serviceCheck)
                                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <!-- Infos du service -->
                                            <div class="flex-1">
                                                <h4 class="text-sm font-medium text-gray-900">{{ $serviceCheck->service->title }}</h4>
                                                @if($serviceCheck->observations)
                                                    <p class="text-sm text-gray-600 mt-1">{{ $serviceCheck->observations }}</p>
                                                @endif
                                            </div>

                                            <!-- Statut et actions -->
                                            <div class="flex items-center gap-4">
                                                <!-- Intervenant -->
                                                <select class="form-select form-select-sm"
                                                        onchange="updateServiceCheck('{{ $serviceCheck->id }}', 'intervenant', this.value)">
                                                    <option value="">Intervenant</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}" 
                                                                {{ $serviceCheck->intervenant == $user->id ? 'selected' : '' }}>
                                                            {{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <!-- Statut -->
                                                <select class="form-select form-select-sm"
                                                        onchange="updateServiceCheck('{{ $serviceCheck->id }}', 'status', this.value)">
                                                    @foreach(['pending' => 'En attente', 'in_progress' => 'En cours', 'success' => 'Validé', 'warning' => 'Avertissement', 'error' => 'Erreur'] as $value => $label)
                                                        <option value="{{ $value }}" 
                                                                {{ $serviceCheck->statut === $value ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <!-- Bouton commentaire -->
                                                <button onclick="openCommentModal('{{ $serviceCheck->id }}')"
                                                        class="p-2 text-gray-500 hover:text-gray-700 transition-colors">
                                                    <i class="bi bi-chat-left-text"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les commentaires -->
<div x-data="{ showCommentModal: false, serviceCheckId: null }" 
     x-show="showCommentModal"
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     style="display: none;">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4" @click.away="showCommentModal = false">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Observations</h3>
            <textarea id="commentTextarea"
                      class="w-full h-32 p-2 border rounded-md mb-4"
                      placeholder="Saisissez vos observations..."></textarea>
            <div class="flex justify-end gap-4">
                <button @click="showCommentModal = false" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Annuler
                </button>
                <button onclick="saveComment()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateServiceCheck(id, field, value) {
        fetch(`/service-checks/${id}/${field}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ [field]: value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Rafraîchir la page pour mettre à jour le statut global
                window.location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function openCommentModal(id) {
        const modal = document.querySelector('[x-data*="showCommentModal"]').__x.$data;
        modal.serviceCheckId = id;
        modal.showCommentModal = true;
    }

    function saveComment() {
        const modal = document.querySelector('[x-data*="showCommentModal"]').__x.$data;
        const comment = document.getElementById('commentTextarea').value;

        updateServiceCheck(modal.serviceCheckId, 'comment', comment);
        modal.showCommentModal = false;
    }
</script>
@endpush 