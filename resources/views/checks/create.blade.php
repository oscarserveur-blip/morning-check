@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    Nouvelle vérification
                </h2>

                <form action="{{ route('checks.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                            <select name="client_id" id="client_id"
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('client_id') border-red-500 @enderror"
                                onchange="window.location.href='?client_id='+this.value">
                                <option value="">Sélectionnez un client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ (old('client_id', request('client_id')) == $client->id) ? 'selected' : '' }}>
                                        {{ $client->label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date_time" class="block text-sm font-medium text-gray-700">Date et heure de vérification</label>
                            <input type="datetime-local" name="date_time" id="date_time" 
                                value="{{ old('date_time') }}"
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('date_time') border-red-500 @enderror">
                            @error('date_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="statut" class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="statut" id="statut"
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('statut') border-red-500 @enderror">
                                <option value="pending" {{ (old('statut') == 'pending') ? 'selected' : '' }}>En attente</option>
                                <option value="completed" {{ (old('statut') == 'completed') ? 'selected' : '' }}>Terminé</option>
                                <option value="failed" {{ (old('statut') == 'failed') ? 'selected' : '' }}>Échoué</option>
                            </select>
                            @error('statut')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if($selectedClient)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4">Catégories et services du client</h3>
                            @forelse($selectedClient->categories as $category)
                                <div class="mb-4 border rounded p-4">
                                    <h4 class="font-bold text-indigo-700 mb-2">{{ $category->title }}</h4>
                                    @if($category->services->count())
                                        <ul class="list-disc pl-6">
                                            @foreach($category->services as $service)
                                                <li>{{ $service->title }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-gray-500">Aucun service dans cette catégorie.</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-gray-500">Aucune catégorie pour ce client.</p>
                            @endforelse
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('checks.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Annuler
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 