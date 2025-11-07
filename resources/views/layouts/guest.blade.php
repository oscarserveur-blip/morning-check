<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            /* Amélioration de la qualité du logo */
            img[src*="btbs-logo"] {
                image-rendering: auto;
                -ms-interpolation-mode: bicubic;
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
            /* Couleurs Bouygues pour les inputs */
            input:focus {
                border-color: #003DA5 !important;
                ring-color: #003DA5 !important;
            }
            input:focus-visible {
                outline-color: #003DA5 !important;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="mb-6">
                <a href="/">
                    @if(file_exists(public_path('btbs-logo.png')))
                        <img src="{{ asset('btbs-logo.png') }}" alt="Bouygues Telecom Business Solutions" style="height: 80px; width: auto; max-width: 300px; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; image-rendering: auto; -ms-interpolation-mode: bicubic;" onerror="this.style.display='none';">
                    @else
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                    @endif
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                @yield('content')
            </div>
        </div>
    </body>
</html>
