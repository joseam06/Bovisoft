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

        @livewireStyles
    </head>

    <body class="min-h-screen bg-gradient-to-r from-red-700 via-red-800 to-black">
        <!-- Opcional: brillo suave arriba para que se vea más pro -->
        <div class="min-h-screen flex items-center justify-center px-4
                    font-sans text-gray-100 antialiased
                    bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.18),transparent_55%)]">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
