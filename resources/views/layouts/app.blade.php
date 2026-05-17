<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#0D0D0D] text-[#E8D9B5] antialiased min-h-screen">
    <div class="min-h-screen flex flex-col">
        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content', '')
        </main>
    </div>

    @livewireScripts
</body>
</html>
