<div class="min-h-screen flex flex-col items-center justify-center p-8"
     x-data="{
         showOverlay: false,
         phaseLabel: '',
         phaseClass: '',
         resultNotif: null,
         resultTimer: null,
         nightEliminated: null,
         nightTimer: null,
     }"
     @transition-phase.window="
         showOverlay = true;
         phaseLabel = $event.detail.label;
         phaseClass = $event.detail.class;
         setTimeout(() => { showOverlay = false; }, 1500);
     "
     @show-result.window="
         resultNotif = $event.detail;
         if (resultTimer) clearTimeout(resultTimer);
         resultTimer = setTimeout(() => { resultNotif = null; }, 6000);
     "
     @show-night-resolved.window="
         nightEliminated = $event.detail;
         if (nightTimer) clearTimeout(nightTimer);
         nightTimer = setTimeout(() => { nightEliminated = null; }, 8000);
     "
>
    {{-- Phase transition overlay --}}
    <div x-show="showOverlay"
         class="fixed inset-0 z-50 flex items-center justify-center"
         :class="phaseClass"
         x-transition:enter="transition-all duration-700"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-all duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        <h2 class="text-4xl font-serif font-bold text-[#E8D9B5]" x-text="phaseLabel"></h2>
    </div>

    {{-- Result notification --}}
    <div x-show="resultNotif" x-cloak
         class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-[#1A1510] border-2 rounded-xl px-6 py-4 shadow-2xl max-w-md w-full text-center"
         :class="{
             'border-[#C8922A]': resultNotif?.type === 'seer' || resultNotif?.type === 'fox',
             'border-[#8B2020]': resultNotif?.type === 'night_resolved' || resultNotif?.type === 'lover_died',
             'border-[#C8922A]/60': resultNotif?.type === 'village_idiot',
         }"
         x-transition:enter="transition-all duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition-all duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Seer result --}}
        <template x-if="resultNotif?.type === 'seer'">
            <div>
                <p class="text-[#9A8A6A] text-xs uppercase tracking-widest" x-text="resultNotif.nickname"></p>
                <p class="text-[#E8D9B5] text-lg mt-1">
                    {{ __('ui.result.faction_label') }}: <span x-text="resultNotif.faction"></span>
                </p>
            </div>
        </template>

        {{-- Fox result --}}
        <template x-if="resultNotif?.type === 'fox'">
            <div>
                <p class="text-[#E8D9B5] text-lg"
                   x-text="resultNotif.found ? '{{ __("ui.result.wolves_found") }}' : '{{ __("ui.result.no_wolves_found") }}'">
                </p>
            </div>
        </template>

        {{-- Lover died --}}
        <template x-if="resultNotif?.type === 'lover_died'">
            <div>
                <p class="text-[#8B2020] text-lg font-bold">{{ __('ui.result.lover_died_title') }}</p>
                <p class="text-[#E8D9B5] mt-1">
                    <span x-text="resultNotif.partner_nickname"></span>
                    {{ __('ui.result.lover_died_text') }}
                </p>
            </div>
        </template>

        {{-- Village Idiot revealed --}}
        <template x-if="resultNotif?.type === 'village_idiot'">
            <div>
                <p class="text-[#C8922A] text-lg font-bold">{{ __('ui.result.idiot_revealed') }}</p>
                <p class="text-[#E8D9B5] mt-1" x-text="resultNotif.nickname"></p>
            </div>
        </template>
    </div>

    {{-- Night resolved notification (separate — does not overwrite role-specific results) --}}
    <div x-show="nightEliminated" x-cloak
         class="fixed top-24 left-1/2 -translate-x-1/2 z-40 bg-[#1A1510] border-2 border-[#8B2020] rounded-xl px-6 py-4 shadow-2xl max-w-md w-full text-center"
         x-transition:enter="transition-all duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition-all duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <p class="text-[#E8D9B5] text-lg font-bold">{{ __('ui.result.night_eliminations') }}</p>
        <div class="mt-2 space-y-1">
            <template x-for="name in nightEliminated.eliminated" :key="name">
                <p class="text-[#8B2020]" x-text="name"></p>
            </template>
        </div>
        <p x-show="nightEliminated.eliminated.length === 0" class="text-[#9A8A6A] italic mt-2">
            {{ __('ui.result.no_deaths') }}
        </p>
    </div>

    {{-- Phase indicator --}}
    <div class="mb-8 text-center">
        <p class="text-[#9A8A6A] text-sm uppercase tracking-widest">
            {{ __('ui.game.round') }} {{ $state->round }}
        </p>
        <h1 class="text-[#E8D9B5] text-3xl font-bold mt-2">
            {{ __("ui.phase.{$state->phase}") }}
        </h1>
    </div>

    @if($state->phase === 'waiting')
        {{-- Ready phase — show role card + ready button --}}
        <livewire:player.role-card :player="$player" :wire:key="'role-'.$player->id" />

        <div class="mt-8 w-full max-w-md text-center">
            @if($ready)
                <div class="bg-[#1A1510] border border-[#3A6B3A] rounded-xl p-6">
                    <div class="text-3xl mb-2 text-[#3A6B3A]">&#10003;</div>
                    <p class="text-[#9A8A6A]">{{ __('ui.game.waiting_for_others') }}</p>
                </div>
            @else
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6">
                    <p class="text-[#9A8A6A] text-sm mb-4">{{ __('ui.game.your_role') }}</p>
                    <button
                        wire:click="readyUp"
                        class="w-full px-6 py-4 bg-[#C8922A] text-[#1A1510] font-bold rounded-xl hover:bg-[#D9A33B] transition-colors text-lg"
                    >
                        {{ __('ui.game.ready_button') }}
                    </button>
                </div>
            @endif
        </div>
    @elseif($state->phase === 'finished')
        {{-- Game Over screen --}}
        <div class="bg-[#1A1510] border-2 border-[#C8922A] rounded-2xl p-8 max-w-lg w-full text-center">
            <h2 class="text-[#C8922A] text-2xl font-bold mb-6">{{ __('ui.game.over') }}</h2>

            @php $winningFaction = $state->data['winning_faction'] ?? 'no_one'; @endphp
            <p class="text-[#E8D9B5] text-lg mb-4">
                {{ __("ui.win.{$winningFaction}") }}
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
        <livewire:player.role-card :player="$player" :wire:key="'role-'.$player->id" />

        @php $lastNightDeaths = $state->data['last_night_deaths'] ?? []; @endphp

        @if(!empty($lastNightDeaths) && in_array($state->phase, ['day', 'voting']))
            <div class="mt-6 w-full max-w-md">
                <div class="bg-[#1A1510] border border-[#8B2020]/50 rounded-xl p-4 text-center">
                    <p class="text-[#9A8A6A] text-xs uppercase tracking-widest mb-2">{{ __('ui.game.last_night') }}</p>
                    <div class="space-y-1">
                        @foreach($lastNightDeaths as $name)
                            <p class="text-[#8B2020] text-sm">{{ $name }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @php
            $mySeerResult = $state->data['seer_results'][$player->id] ?? null;
            $myFoxResult = $state->data['fox_results'][$player->id] ?? null;
        @endphp

        @if(($mySeerResult || $myFoxResult) && in_array($state->phase, ['day', 'voting']))
            <div class="mt-6 w-full max-w-md"
                 x-data="{ revealed: false }"
                 x-on:mousedown="revealed = true"
                 x-on:mouseup="revealed = false"
                 x-on:mouseleave="revealed = false"
                 x-on:touchstart="revealed = true"
                 x-on:touchend="revealed = false">
                {{-- Masked face (looks like default day prompt) --}}
                <div x-show="!revealed"
                     class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                    <p class="text-[#9A8A6A]">{{ __('ui.game.discussion_time') }}</p>
                </div>
                {{-- Revealed face --}}
                <div x-show="revealed" x-cloak
                     class="bg-[#1A1510] border border-[#C8922A]/60 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[#C8922A] text-xs uppercase tracking-widest">{{ __('ui.result.your_results') }}</p>
                    </div>
                    @if($mySeerResult)
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-[#9A8A6A] text-xs">{{ $mySeerResult['target_nickname'] }}</p>
                                <p class="text-[#E8D9B5] text-sm">
                                    {{ __('ui.result.faction_label') }}: {{ __("ui.factions.{$mySeerResult['faction']}") }}
                                </p>
                            </div>
                            <button wire:click="dismissResult('seer')"
                                    class="text-[#6A5A4A] hover:text-[#C8922A] text-lg leading-none">&times;</button>
                        </div>
                    @endif
                    @if($myFoxResult)
                        <div class="flex justify-between items-start">
                            <p class="text-[#E8D9B5] text-sm">
                                {{ $myFoxResult['werewolf_found'] ? __('ui.result.wolves_found') : __('ui.result.no_wolves_found') }}
                            </p>
                            <button wire:click="dismissResult('fox')"
                                    class="text-[#6A5A4A] hover:text-[#C8922A] text-lg leading-none">&times;</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="mt-8 w-full max-w-md">
            @if(!$player->is_alive)
                <div class="bg-[#1A1510] border border-[#8B2020] rounded-xl p-6 text-center">
                    <p class="text-[#8B2020]">{{ __('ui.game.you_are_dead') }}</p>
                </div>
            @elseif($state->phase === 'night' && !$player->is_narrator)
                <livewire:player.night-action :room="$room" :player="$player" :wire:key="'night-action-'.$player->id" />
            @elseif($state->phase === 'voting' && !$player->is_narrator)
                <livewire:player.voting-panel :room="$room" :player="$player" :wire:key="'voting-'.$player->id" />
            @elseif($state->phase === 'day' && !$mySeerResult && !$myFoxResult)
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 text-center">
                    <p class="text-[#9A8A6A]">{{ __('ui.game.discussion_time') }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
