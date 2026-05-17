<div class="min-h-screen flex flex-col items-center justify-center p-8">
    {{-- Phase indicator --}}
    <div class="mb-8 text-center">
        <p class="text-[#9A8A6A] text-sm uppercase tracking-widest">
            {{ __('ui.game.round') }} {{ $state->round }}
        </p>
        <h1 class="text-[#E8D9B5] text-3xl font-bold mt-2">
            {{ __("ui.phase.{$state->phase}") }}
        </h1>
    </div>

    @if($state->phase === 'finished')
        {{-- Game Over screen --}}
        <div class="bg-[#1A1510] border-2 border-[#C8922A] rounded-2xl p-8 max-w-lg w-full text-center">
            <h2 class="text-[#C8922A] text-2xl font-bold mb-6">{{ __('ui.game.over') }}</h2>

            <p class="text-[#E8D9B5] text-lg mb-4">
                {{ __("ui.win.{$state->data['winning_faction'] ?? 'no_one'}") }}
            </p>

            {{-- All players with roles revealed --}}
            <div class="space-y-2 mb-6 text-left">
                @foreach($players as $p)
                    <div class="flex items-center justify-between px-3 py-2 bg-[#251E16]/50 rounded-lg
                        {{ $p->is_alive ? 'border border-[#C8922A]/30' : 'opacity-50' }}">
                        <span class="text-[#E8D9B5] text-sm {{ !$p->is_alive ? 'line-through' : '' }}">
                            {{ $p->nickname }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-[#6A5A4A] text-xs uppercase">{{ __("ui.factions.{$p->role->faction}") }}</span>
                            <span class="text-[#C8922A] text-xs">{{ $p->role ? __("roles.{$p->role->key}.name") : '?' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-[#9A8A6A] text-xs italic">{{ __('ui.narrator.waiting_for_new_game') }}</p>
        </div>
    @else
        {{-- Normal game view --}}
        <livewire:role-card :player="$player" :wire:key="'role-'.$player->id" />

        <div class="mt-8 w-full max-w-md">
            @if(!$player->is_alive)
                <div class="bg-[#1A1510] border border-[#8B2020] rounded-xl p-6 text-center">
                    <p class="text-[#8B2020]">{{ __('ui.game.you_are_dead') }}</p>
                </div>
            @elseif($state->phase === 'night' && $player->role && $player->role->night_order !== null && !$player->is_narrator)
                <livewire:night-action-panel :room="$room" :player="$player" :wire:key="'night-action-'.$player->id" />
            @elseif($state->phase === 'night')
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                    <p class="text-[#9A8A6A] italic">{{ __('ui.game.decoy_prompt') }}</p>
                </div>
            @elseif($state->phase === 'voting' && !$player->is_narrator)
                <livewire:voting-panel :room="$room" :player="$player" :wire:key="'voting-'.$player->id" />
            @elseif($state->phase === 'day')
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                    <p class="text-[#9A8A6A]">{{ __('ui.game.discussion_time') }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
