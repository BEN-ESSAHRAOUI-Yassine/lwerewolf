<div class="min-h-screen p-6"
     x-data="{ showOverlay: false, phaseLabel: '', phaseClass: '' }"
     @transition-phase.window="
         showOverlay = true;
         phaseLabel = $event.detail.label;
         phaseClass = $event.detail.class;
         setTimeout(() => { showOverlay = false; }, 1500);
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

    <div class="max-w-7xl mx-auto">
        {{-- ===== HEADER ===== --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-[#9A8A6A] text-xs uppercase tracking-widest">
                        {{ __('ui.game.round') }} {{ $state->round }}
                    </p>
                    <h1 class="text-[#E8D9B5] text-2xl font-bold">
                        {{ __("ui.phase.{$state->phase}") }}
                    </h1>
                </div>
                <span class="text-[#5C4A1A] text-sm bg-[#5C4A1A]/10 px-2 py-1 rounded font-mono">
                    {{ $room->code }}
                </span>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-[#9A8A6A] text-xs">{{ __('ui.game.players_alive') }}</p>
                    <p class="text-[#C8922A] text-xl font-bold">{{ $totalAlive }} / {{ $players->count() }}</p>
                </div>
                @if($state->phase === 'night' && count($pendingRoles) > 0)
                    <div class="text-right">
                        <p class="text-[#8AB8E8] text-xs">{{ __('ui.narrator.pending_actions') }}</p>
                        <div class="flex flex-wrap gap-1 justify-end mt-1 max-w-48">
                            @foreach(array_unique($pendingRoles) as $roleKey)
                                <span class="text-[#6A9AB8] text-xs bg-[#1A3A5C]/50 px-1.5 py-0.5 rounded">
                                    {{ __("roles.{$roleKey}.name") }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== PHASE CONTROLS ===== --}}
        @if(count($availableTransitions) > 0 && $state->phase !== 'finished')
            <div class="flex flex-wrap gap-3 mb-6 justify-center">
                @foreach($availableTransitions as $target)
                    <button
                        wire:click="advancePhase('{{ $target }}')"
                        wire:confirm="{{ __('ui.narrator.confirm_transition') }}"
                        class="px-6 py-3 rounded-lg font-semibold transition-colors duration-200 text-sm
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

        {{-- ===== MAIN CONTENT: 2-COLUMN LAYOUT ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- LEFT: Player grid (3/5 width) --}}
            <div class="lg:col-span-3">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @forelse($players as $p)
                        @php
                            $isLover = isset($loverMap[$p->id]);
                            $isEnchanted = in_array($p->id, $enchantedIds);
                        @endphp
                        <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-3 transition-all duration-200
                            {{ !$p->is_alive ? 'opacity-40 border-[#8B2020]/30' : '' }}
                            {{ $isLover ? 'border-pink-900/40' : '' }}
                            {{ $isEnchanted ? 'border-green-900/40' : '' }}"
                            title="{{ $p->nickname }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[#E8D9B5] text-sm font-medium truncate max-w-[10rem]
                                    {{ !$p->is_alive ? 'line-through text-[#6A5A4A]' : '' }}">
                                    {{ $p->nickname }}
                                </span>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    @if(!$p->is_alive)
                                        <span class="text-[#8B2020] text-[10px] bg-[#8B2020]/10 px-1.5 py-0.5 rounded">{{ __('ui.game.dead') }}</span>
                                    @elseif($p->voting_banned)
                                        <span class="text-[#8B5A20] text-[10px] bg-[#8B5A20]/10 px-1.5 py-0.5 rounded">{{ __('ui.vote.banned_short') }}</span>
                                    @endif
                                    @if($isLover)
                                        <span class="text-pink-600 text-xs bg-pink-900/10 px-1.5 py-0.5 rounded">{{ __('ui.game.lover_short') }}</span>
                                    @endif
                                    @if($isEnchanted)
                                        <span class="text-green-600 text-xs bg-green-900/10 px-1.5 py-0.5 rounded">{{ __('ui.game.enchanted_short') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if($p->role)
                                <div class="flex items-center gap-2">
                                    <span class="text-[#6A5A4A] text-[10px] uppercase tracking-wider">{{ __("ui.factions.{$p->role->faction}") }}</span>
                                    <span class="text-[#C8922A] text-xs">{{ __("roles.{$p->role->key}.name") }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="text-[#5C4A1A] text-[10px]">{{ __("roles.{$p->role->key}.description") }}</span>
                                </div>
                            @else
                                <p class="text-[#5C4A1A] text-xs italic">{{ __('ui.game.no_role') }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-[#9A8A6A] col-span-full text-center italic">{{ __('ui.narrator.no_players') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- RIGHT: Action feed / game log (2/5 width) --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Vote tally (during voting) --}}
                @if($state->phase === 'voting')
                    <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-[#E8D9B5] text-sm font-semibold">{{ __('ui.vote.ongoing') }}</h2>
                            <span class="text-[#9A8A6A] text-xs">{{ $voteCount }} / {{ $players->where('is_alive', true)->where('voting_banned', false)->count() }} {{ __('ui.vote.cast') }}</span>
                        </div>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto">
                            @forelse($voteTally as $targetId => $count)
                                @php $p = $players->firstWhere('id', $targetId); @endphp
                                @if($p)
                                    <div class="flex justify-between items-center px-2.5 py-1.5 bg-[#251E16]/50 rounded text-sm">
                                        <span class="text-[#E8D9B5] truncate mr-2">{{ $p->nickname }}</span>
                                        <span class="text-[#C8922A] font-mono text-xs">{{ $count }}</span>
                                    </div>
                                @endif
                            @empty
                                <p class="text-[#9A8A6A] text-xs text-center italic">{{ __('ui.vote.no_votes_yet') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Night action feed --}}
                @if($state->phase === 'night')
                    <div class="bg-[#1A1510] border border-[#1A3A5C]/50 rounded-xl p-4">
                        <h2 class="text-[#8AB8E8] text-sm font-semibold mb-3 flex items-center justify-between">
                            <span>{{ __('ui.narrator.action_feed') }}</span>
                            <span class="text-[#6A9AB8] text-xs bg-[#1A3A5C]/30 px-2 py-0.5 rounded">{{ count($nightActionFeed) }}</span>
                        </h2>
                        <div class="space-y-1.5 max-h-64 overflow-y-auto">
                            @forelse($nightActionFeed as $action)
                                <div class="px-2.5 py-1.5 bg-[#1A3A5C]/20 rounded text-xs border-l-2 border-[#3A6A9A]">
                                    <div class="flex items-center justify-between text-[#8AB8E8]">
                                        <span>{{ $action['role_key'] ? __("roles.{$action['role_key']}.name") : __('ui.game.unknown_role') }}</span>
                                        <span class="text-[#6A9AB8] text-[10px]">{{ \Carbon\Carbon::parse($action['timestamp'])->isoFormat('HH:mm:ss') }}</span>
                                    </div>
                                    <p class="text-[#B8D8E8] mt-0.5">
                                        @if($action['action_type'] === 'inspect')
                                            {{ __('ui.narrator.action_inspect', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'kill')
                                            {{ __('ui.narrator.action_kill', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'protect')
                                            {{ __('ui.narrator.action_protect', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'save')
                                            {{ __('ui.narrator.action_save', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'poison')
                                            {{ __('ui.narrator.action_poison', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'convert')
                                            {{ __('ui.narrator.action_convert', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'enchant')
                                            {{ __('ui.narrator.action_enchant', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'sniff')
                                            {{ __('ui.narrator.action_sniff', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'link_lovers')
                                            {{ __('ui.narrator.action_love', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'choose_side')
                                            {{ __('ui.narrator.action_choose_side') }}
                                        @elseif($action['action_type'] === 'extra_kill')
                                            {{ __('ui.narrator.action_extra_kill', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @elseif($action['action_type'] === 'solo_kill')
                                            {{ __('ui.narrator.action_solo_kill', ['name' => $action['target_nickname'] ?? '?']) }}
                                        @else
                                            {{ __('ui.narrator.action_generic', ['type' => $action['action_type'], 'name' => $action['target_nickname'] ?? '?']) }}
                                        @endif
                                    </p>
                                </div>
                            @empty
                                <p class="text-[#6A9AB8] text-xs text-center italic">{{ __('ui.narrator.no_actions_yet') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Game log --}}
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-4">
                    <h2 class="text-[#9A8A6A] text-sm font-semibold mb-3">{{ __('ui.narrator.game_log') }}</h2>
                    <div class="space-y-1 max-h-72 overflow-y-auto">
                        @forelse(array_reverse($gameLog) as $entry)
                            <div class="text-xs px-2 py-1 rounded
                                {{ $entry['type'] === 'phase_changed' ? 'text-[#C8922A] bg-[#C8922A]/5' : '' }}
                                {{ $entry['type'] === 'player_eliminated' ? 'text-[#8B2020] bg-[#8B2020]/5' : '' }}
                                {{ $entry['type'] === 'night_resolved' ? 'text-[#8AB8E8] bg-[#8AB8E8]/5' : '' }}
                                {{ $entry['type'] === 'vote_submitted' ? 'text-[#9A8A6A] bg-[#9A8A6A]/5' : '' }}
                                {{ $entry['type'] === 'voting_resolved' ? 'text-[#E8A88A] bg-[#E8A88A]/5' : '' }}
                                {{ $entry['type'] === 'suspicious_access' ? 'text-[#E8B5B5] bg-[#8B2020]/10' : '' }}
                                {{ $entry['type'] === 'game_started' ? 'text-[#C8922A] bg-[#C8922A]/10' : '' }}">
                                <span class="text-[#5C4A1A] mr-1.5">{{ \Carbon\Carbon::parse($entry['timestamp'])->isoFormat('HH:mm') }}</span>
                                @if($entry['type'] === 'phase_changed')
                                    [{{ __('ui.phase.' . ($entry['from'] ?? 'unknown')) }} → {{ __('ui.phase.' . ($entry['to'] ?? 'unknown')) }}]
                                @elseif($entry['type'] === 'player_eliminated')
                                    {{ __('ui.narrator.log_eliminated', ['name' => $entry['nickname'] ?? '?']) }}
                                @elseif($entry['type'] === 'night_resolved')
                                    {{ __('ui.narrator.log_night_resolved') }}
                                @elseif($entry['type'] === 'vote_submitted')
                                    {{ __('ui.narrator.log_vote_cast') }}
                                @elseif($entry['type'] === 'voting_resolved')
                                    {{ __('ui.narrator.log_voting_resolved') }}
                                @elseif($entry['type'] === 'suspicious_access')
                                    {{ __('ui.narrator.log_suspicious', ['nickname' => $entry['player_nickname'] ?? '?', 'details' => $entry['details'] ?? '']) }}
                                @elseif($entry['type'] === 'game_started')
                                    {{ __('ui.narrator.log_game_started') }}
                                @elseif($entry['type'] === 'game_finished')
                                    {{ __('ui.narrator.log_game_finished', ['faction' => __("ui.win.{$entry['winning_faction']}")]) }}
                                @else
                                    {{ $entry['type'] }}
                                @endif
                            </div>
                        @empty
                            <p class="text-[#5C4A1A] text-xs text-center italic">{{ __('ui.narrator.log_empty') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Action history (always visible) --}}
                <div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-4">
                    <h2 class="text-[#E8D9B5] text-sm font-semibold mb-3">{{ __('ui.narrator.action_history') }}</h2>
                    <div class="space-y-1.5 max-h-64 overflow-y-auto">
                        @forelse(array_reverse($actionHistory) as $action)
                            <div class="px-2.5 py-1.5 bg-[#251E16]/30 rounded text-xs border-l-2 border-[#5C4A1A]">
                                <div class="flex items-center justify-between text-[#9A8A6A]">
                                    <span>R{{ $action['round'] }} — {{ $action['player_nickname'] }} ({{ __("roles.{$action['role_key']}.name") }})</span>
                                    <span class="text-[#6A5A4A] text-[10px]">{{ \Carbon\Carbon::parse($action['timestamp'])->isoFormat('HH:mm') }}</span>
                                </div>
                                <p class="text-[#C8922A] mt-0.5">
                                    @if($action['action_type'] === 'inspect')
                                        {{ __('ui.narrator.action_inspect', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'kill')
                                        {{ __('ui.narrator.action_kill', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'extra_kill')
                                        {{ __('ui.narrator.action_extra_kill', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'convert')
                                        {{ __('ui.narrator.action_convert', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'solo_kill')
                                        {{ __('ui.narrator.action_solo_kill', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'protect')
                                        {{ __('ui.narrator.action_protect', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'save')
                                        {{ __('ui.narrator.action_save', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'poison')
                                        {{ __('ui.narrator.action_poison', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'enchant')
                                        {{ __('ui.narrator.action_enchant', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'sniff')
                                        {{ __('ui.narrator.action_sniff', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'link_lovers')
                                        {{ __('ui.narrator.action_love', ['name' => $action['target_nickname'] ?? '?']) }}
                                    @elseif($action['action_type'] === 'choose_side')
                                        {{ __('ui.narrator.action_choose_side') }}
                                    @else
                                        {{ __('ui.narrator.action_generic', ['type' => $action['action_type'], 'name' => $action['target_nickname'] ?? '?']) }}
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-[#5C4A1A] text-xs text-center italic">{{ __('ui.narrator.no_actions_yet') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== GAME OVER SCREEN ===== --}}
        @if($state->phase === 'finished')
            <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50">
                <div class="bg-[#1A1510] border-2 border-[#C8922A] rounded-2xl p-8 max-w-lg w-full mx-4 text-center">
                    <h2 class="text-[#C8922A] text-2xl font-bold mb-2">{{ __('ui.game.over') }}</h2>
                    <p class="text-[#E8D9B5] text-xl mb-6">
                        @php $winningFaction = $state->data['winning_faction'] ?? 'no_one'; @endphp
                        {{ __("ui.win.{$winningFaction}") }}
                    </p>

                    {{-- All players with their roles revealed --}}
                    <div class="space-y-2 mb-8 max-h-64 overflow-y-auto">
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

                    <button
                        wire:click="newGame"
                        wire:confirm="{{ __('ui.narrator.confirm_new_game') }}"
                        class="px-8 py-3 bg-[#C8922A] text-[#1A1510] font-bold rounded-lg hover:bg-[#D8A23A] transition-colors duration-200"
                    >
                        {{ __('ui.narrator.new_game') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
