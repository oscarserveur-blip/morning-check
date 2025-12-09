@extends('layouts.guest')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                    Mot de passe oublié
                </h2>

                <div class="mb-4 text-sm text-gray-600">
                    {{ __('Pas de problème. Indiquez-nous simplement votre adresse e-mail et nous vous enverrons un lien de réinitialisation de mot de passe qui vous permettra d\'en choisir un nouveau.') }}
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Adresse email</label>
                        <input id="email" name="email" type="email" required autofocus
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                            value="{{ old('email') }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('login') }}" class="text-sm font-medium" style="color: #4A90E2;" onmouseover="this.style.color='#357ABD';" onmouseout="this.style.color='#4A90E2';">
                            Retour à la connexion
                        </a>
                        <button type="submit" class="flex justify-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white" style="background-color: #4A90E2; border-color: #4A90E2;" onmouseover="this.style.backgroundColor='#357ABD'; this.style.borderColor='#357ABD';" onmouseout="this.style.backgroundColor='#4A90E2'; this.style.borderColor='#4A90E2';">
                            {{ __('Envoyer le lien de réinitialisation') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
