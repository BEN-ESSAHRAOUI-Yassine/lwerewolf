@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-sm mx-auto text-center">
        <h1 class="font-serif text-4xl text-[#E8D9B5] mb-2 tracking-wide">{{ config('app.name') }}</h1>
        <p class="text-[#9A8A6A] text-sm mb-12">Les Loups-Garous de Thiercelieux</p>

        <div class="space-y-4">
            <a href="{{ route('rooms.create') }}" class="block w-full py-3 px-6 bg-[#C8922A] text-[#0D0D0D] font-semibold rounded-lg text-center hover:bg-[#D4A235] transition-colors duration-200">
                {{ __('ui.button.create_room') }}
            </a>
            <a href="{{ route('rooms.join') }}" class="block w-full py-3 px-6 border border-[#C8922A] text-[#C8922A] font-semibold rounded-lg text-center hover:bg-[#C8922A] hover:text-[#0D0D0D] transition-colors duration-200">
                {{ __('ui.button.join_room') }}
            </a>
        </div>
    </div>
</div>
@endsection
