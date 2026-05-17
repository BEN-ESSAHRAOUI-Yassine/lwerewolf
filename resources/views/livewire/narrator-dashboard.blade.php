<div class="min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-[#9A8A6A] text-sm uppercase tracking-widest">
                    {{ __('ui.game.round') }} {{ $state->round }}
                </p>
                <h1 class="text-[#E8D9B5] text-3xl font-bold mt-2">
                    {{ __("ui.phase.{$state->phase}") }}
                </h1>
                <p class="text-[#9A8A6A] text-sm mt-1">
                    {{ $room->code }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-[#9A8A6A] text-sm">{{ __('ui.game.players_alive') }}:</p>
                <p class="text-[#C8922A] text-2xl font-bold">{{ $totalAlive }} / {{ $players->count() }}</p>
            </div>
        </div>

        {{-- Phase transition buttons --}}
        @if(count($availableTransitions) > 0 && $state->phase !== 'finished')
            <div class="flex flex-wrap gap-4 mb-8 justify-center">
                @foreach($availableTransitions as $target)
                    <button
                        wire:click="advancePhase('{{ $target }}')"
                        class="px-6 py-3 rounded-lg font-semibold transition-colors duration-200
                            {{ $target === 'night' ? 'bg-[#1A3A5C] text-[#8AB8E8] hover:bg-[#2A4A6C]' : '' }}
                            {{ $target === 'day' ? 'bg-[#5C4A1A] text-[#E8D89A] hover:bg-[#6C5A2A]' : '' }}
                            {{ $target === 'voting' ? 'bg-[#5C2A1A] text-[#E8A88A] hover:bg-[#6C3A2A]' : '' }}
                            {{ $target === 'finished' ? 'bg-[#8B2020] text-[#E8B5B5] hover:bg-[#9B3030]' : '' }}"
                    >
                        {{ __("ui.phase.go_to_{$target}") }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Vote tally during voting --}}
        @if($state->phase === 'voting')
            <div class="mb-8 bg-[#1A1510] border border-[#251E16] rounded-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-[#E8D9B5] font-semibold">{{ __('ui.vote.ongoing') }}</h2>
                    <span class="text-[#9A8A6A] text-sm">{{ $voteCount }} / {{ $players->where('is_alive', true)->where('voting_banned', false)->count() }} {{ __('ui.vote.cast') }}</span>
                </div>
                <div class="space-y-2">
                    @forelse($voteTally as $targetId => $count)
                        @php $p = $players->firstWhere('id', $targetId); @endphp
                        @if($p)
                            <div class="flex justify-between items-center px-3 py-2 bg-[#251E16]/50 rounded">
                                <span class="text-[#E8D9B5]">{{ $p->nickname }}</span>
                                <span class="text-[#C8922A] font-mono">{{ $count }}</span>
                            </div>
                        @endif
                    @empty
                        <p class="text-[#9A8A6A] text-sm text-center italic">{{ __('ui.vote.no_votes_yet') }}</p>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- Players grid --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($players as $p)
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-4 {{ !$p->is_alive ? 'opacity-50' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[#E8D9B5] font-medium {{ !$p->is_alive ? 'line-through' : '' }}">{{ $p->nickname }}</span>
                        @if(!$p->is_alive)
                            <span class="text-[#8B2020] text-xs bg-[#8B2020]/10 px-2 py-0.5 rounded">{{ __('ui.game.dead') }}</span>
                        @elseif($p->voting_banned)
                            <span class="text-[#8B2020] text-xs bg-[#8B2020]/10 px-2 py-0.5 rounded">{{ __('ui.vote.banned_short') }}</span>
                        @endif
                    </div>
                    @if($p->role)
                        <div class="flex items-center gap-2">
                            <span class="text-[#9A8A6A] text-xs">{{ __("ui.factions.{$p->role->faction}") }}</span>
                            <span class="text-[#C8922A] text-xs">{{ __("roles.{$p->role->key}.name") }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
