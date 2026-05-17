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

    {{-- Role card --}}
    <livewire:role-card :player="$player" :wire:key="'role-'.$player->id" />

    {{-- Phase-specific panels --}}
    <div class="mt-8 w-full max-w-md">
        @if(!$player->is_alive)
            <div class="bg-[#1A1510] border border-[#8B2020] rounded-xl p-6 text-center">
                <p class="text-[#8B2020]">{{ __('ui.game.you_are_dead') }}</p>
            </div>
        @elseif($state->phase === 'night' && $player->role && $player->role->night_order !== null)
            <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                <p class="text-[#9A8A6A] italic">{{ __('ui.game.waiting_night_action') }}</p>
            </div>
        @elseif($state->phase === 'night')
            <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                <p class="text-[#9A8A6A] italic">{{ __('ui.game.decoy_prompt') }}</p>
            </div>
        @elseif($state->phase === 'day')
            <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                <p class="text-[#9A8A6A]">{{ __('ui.game.discussion_time') }}</p>
            </div>
        @elseif($state->phase === 'voting' && !$player->voting_banned)
            <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                <p class="text-[#9A8A6A]">{{ __('ui.vote.title') }}</p>
            </div>
        @elseif($state->phase === 'finished')
            <div class="bg-[#1A1510] border border-[#C8922A] rounded-xl p-6 text-center">
                <p class="text-[#C8922A] text-lg font-bold">{{ __('ui.game.over') }}</p>
            </div>
        @endif
    </div>
</div>
