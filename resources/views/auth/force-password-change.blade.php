@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg">
        <div class="mb-6">
            @if(file_exists(public_path('btbs-logo.png')))
                <img src="{{ asset('btbs-logo.png') }}" alt="Bouygues Telecom Business Solutions" style="height: 60px; width: auto; max-width: 200px; margin: 0 auto 20px; display: block; image-rendering: auto; -ms-interpolation-mode: bicubic; image-rendering: -webkit-optimize-contrast;">
            @else
                <x-application-logo class="w-20 h-20 fill-current text-gray-500 mx-auto mb-4" />
            @endif
        </div>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Changement de mot de passe requis</h2>
            <p class="text-sm text-center text-gray-600">
                Pour des raisons de sécurité, vous devez définir un nouveau mot de passe fort.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <ul class="text-sm text-red-600 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.force-change') }}">
            @csrf

            <!-- Current Password -->
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Mot de passe actuel <span class="text-red-500">*</span>
                </label>
                <input id="current_password" 
                       name="current_password" 
                       type="password" 
                       required 
                       autofocus
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-500 @error('current_password') border-red-500 @enderror">
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Nouveau mot de passe <span class="text-red-500">*</span>
                </label>
                <input id="password" 
                       name="password" 
                       type="password" 
                       required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Le mot de passe doit contenir au moins 8 caractères, incluant des majuscules, minuscules, chiffres et symboles.
                </p>
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirmer le nouveau mot de passe <span class="text-red-500">*</span>
                </label>
                <input id="password_confirmation" 
                       name="password_confirmation" 
                       type="password" 
                       required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-500">
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="w-full flex justify-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white" style="background-color: #4A90E2; border-color: #4A90E2;" onmouseover="this.style.backgroundColor='#357ABD'; this.style.borderColor='#357ABD';" onmouseout="this.style.backgroundColor='#4A90E2'; this.style.borderColor='#4A90E2';">
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

