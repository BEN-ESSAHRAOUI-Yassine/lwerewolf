<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#0D0D0D] text-[#E8D9B5] antialiased min-h-screen font-sans">
    {{-- Atmospheric background layers --}}
    <div class="fog-layer"></div>
    <div class="vignette"></div>

    <div class="min-h-screen flex flex-col relative z-10">
        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content', '')
        </main>
    </div>

    @livewireScripts
</body>
</html>
