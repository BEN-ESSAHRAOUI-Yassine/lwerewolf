<div wire:poll.3s>
    {{-- Header --}}
    <div class="flex flex-col items-center mb-8">
        <p class="text-[#9A8A6A] text-sm mb-1">{{ __('ui.lobby.room_code') }}</p>
        <p class="font-mono text-4xl tracking-[0.3em] text-[#C8922A] font-bold">{{ $room->code }}</p>
    </div>

    {{-- QR Code + Players --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-4xl mx-auto">
        {{-- Left: QR Code --}}
        <div class="flex flex-col items-center">
            <div class="bg-white p-4 rounded-lg mb-4">
                <img src="{{ $qrSvg }}" alt="QR Code" class="w-48 h-48">
            </div>
            <p class="text-[#9A8A6A] text-sm">{{ __('ui.lobby.scan_to_join') }}</p>

            {{-- Player Count --}}
            <div class="mt-6 text-center">
                <span class="text-[#E8D9B5] text-lg">{{ $playerCount }}</span>
                <span class="text-[#9A8A6A]"> / 24 {{ __('ui.lobby.players') }}</span>
            </div>
        </div>

        {{-- Right: Players --}}
<div wire:poll.3s>
            <h2 class="text-[#E8D9B5] font-semibold mb-4">{{ __('ui.lobby.connected_players') }}</h2>
            <livewire:shared.player-list :room="$room" :wire:key="'player-list-'.$room->id" />
        </div>
    </div>

    {{-- Role Configuration --}}
    <div class="max-w-4xl mx-auto mt-12">
        <h2 class="text-[#E8D9B5] font-semibold mb-4 text-lg">{{ __('ui.lobby.role_config') }}</h2>

        @foreach($roles as $faction => $factionRoles)
            <div class="mb-6">
                <h3 class="text-[#9A8A6A] text-sm uppercase tracking-wider mb-3">{{ __("ui.factions.$faction") }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($factionRoles as $role)
                        @php $count = $roleCounts[$role->key] ?? 0; @endphp
                        <div class="flex items-center justify-between px-4 py-3 bg-[#1A1510] rounded-lg border border-[#251E16]">
                            <span class="text-[#E8D9B5] text-sm">{{ __("roles.{$role->key}.name") }}</span>
                            <div class="flex items-center gap-2">
                                <button wire:click="decrementRole('{{ $role->key }}')" class="w-8 h-8 flex items-center justify-center bg-[#251E16] text-[#9A8A6A] rounded hover:bg-[#3A3530] transition-colors">-</button>
                                <span class="text-[#C8922A] font-mono w-6 text-center">{{ $count }}</span>
                                <button wire:click="incrementRole('{{ $role->key }}')" class="w-8 h-8 flex items-center justify-center bg-[#251E16] text-[#9A8A6A] rounded hover:bg-[#3A3530] transition-colors">+</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Total --}}
        <div class="text-center mb-4">
            <span class="text-[#9A8A6A]">{{ __('ui.lobby.assigned') }}: </span>
            <span class="text-[#C8922A] font-mono">{{ array_sum($roleCounts) }}</span>
            <span class="text-[#9A8A6A]"> / {{ $playerCount }}</span>
            <span class="text-[#9A8A6A]"> {{ __('ui.lobby.players') }}</span>
        </div>

        {{-- Validation Errors --}}
        @if(!empty($validationErrors))
            <div class="mb-4 p-4 bg-[#251E16] border border-[#8B2020] rounded-lg">
                @foreach($validationErrors as $error)
                    <p class="text-[#8B2020] text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Start Game --}}
        <div class="text-center">
            <button
                wire:click="startGame"
                @if(!$canStart) disabled @endif
                class="w-full max-w-xs py-4 px-8 font-bold text-lg rounded-lg transition-colors duration-200 {{ $canStart ? 'bg-[#3A6B3A] text-[#E8D9B5] hover:bg-[#4A7B4A] cursor-pointer' : 'bg-[#3A3530] text-[#6A6560] cursor-not-allowed' }}"
            >
                {{ __('ui.button.start_game') }}
            </button>
        </div>
    </div>
</div>
