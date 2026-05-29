<div class="min-h-screen flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-sm mx-auto text-center">
        {{-- Room Code --}}
        <p class="text-[#9A8A6A] text-sm mb-1">{{ __('ui.lobby.room_code') }}</p>
        <p class="font-mono text-2xl tracking-[0.3em] text-[#C8922A] font-bold mb-8">{{ $room->code }}</p>

        {{-- Player Nickname --}}
        <div class="mb-8">
            <p class="text-[#E8D9B5] text-lg">{{ __('ui.lobby.you_joined_as') }}</p>
            <p class="font-serif text-2xl text-[#C8922A] mt-1">{{ $player->nickname }}</p>
        </div>

        {{-- Players --}}
        <div class="mb-8">
            <h2 class="text-[#9A8A6A] text-sm uppercase tracking-wider mb-4">{{ __('ui.lobby.connected_players') }}</h2>
            <livewire:shared.player-list :room="$room" :wire:key="'player-list-'.$room->id" />
        </div>

        {{-- Waiting State --}}
        <div class="mt-8">
            <div class="animate-pulse flex items-center justify-center gap-2">
                <div class="w-2 h-2 bg-[#C8922A] rounded-full"></div>
                <div class="w-2 h-2 bg-[#C8922A] rounded-full animation-delay-200"></div>
                <div class="w-2 h-2 bg-[#C8922A] rounded-full animation-delay-400"></div>
            </div>
            <p class="text-[#9A8A6A] mt-4">{{ __('ui.lobby.waiting_narrator') }}</p>
        </div>
    </div>
</div>
