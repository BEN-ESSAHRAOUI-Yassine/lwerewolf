<div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 w-full max-w-md"
     x-data="{ revealed: false }">
    @if($banned)
        <div class="text-center py-4">
            <p class="text-[#8B2020]">{{ __('ui.vote.banned') }}</p>
        </div>
    @elseif($submitted)
        <div class="text-center"
             x-on:mousedown="revealed = true"
             x-on:mouseup="revealed = false"
             x-on:mouseleave="revealed = false"
             x-on:touchstart="revealed = true"
             x-on:touchend="revealed = false"
        >
            <div x-show="!revealed" class="py-4">
                <div class="text-3xl mb-2 text-[#C8922A]/40">&#10003;</div>
                <p class="text-[#9A8A6A]">{{ __('ui.vote.submitted') }}</p>
            </div>
            <div x-show="revealed" x-cloak class="py-4">
                <p class="text-[#9A8A6A] text-xs">{{ __('ui.vote.title') }}</p>
                @php $submittedVote = \App\Models\Vote::where('game_state_id', $room->gameState->id)->where('voter_id', $player->id)->first(); @endphp
                @if($submittedVote && $submittedVote->target)
                    <p class="text-[#C8922A] mt-2">{{ $submittedVote->target->nickname }}</p>
                @endif
            </div>
        </div>
    @elseif($confirming && $selectedTargetId)
        <div class="text-center">
            <p class="text-[#9A8A6A] mb-4">{{ __('ui.vote.confirm') }}</p>
            @php $targetName = collect($alivePlayers)->firstWhere('id', $selectedTargetId)['nickname'] ?? ''; @endphp
            <p class="text-[#E8D9B5] text-xl mb-6">{{ $targetName }}</p>
            <div class="flex gap-4 justify-center">
                <button wire:click="cancelSelection" class="px-6 py-2 bg-[#251E16] text-[#9A8A6A] rounded-lg hover:bg-[#3A3530]">{{ __('ui.button.cancel') }}</button>
                <button wire:click="confirmVote" class="px-6 py-2 bg-[#5C2A1A] text-[#E8A88A] rounded-lg hover:bg-[#6C3A2A]">{{ __('ui.button.confirm') }}</button>
            </div>
        </div>
    @else
        <div>
            <p class="text-[#9A8A6A] text-sm mb-4 text-center">{{ __('ui.vote.title') }}</p>

            {{-- Live tally --}}
            @if(count($liveTally) > 0)
                <div class="mb-4 space-y-1">
                    @foreach($liveTally as $t)
                        <div class="flex justify-between text-sm px-2 py-1 bg-[#251E16]/50 rounded">
                            <span class="text-[#E8D9B5]">{{ $t['nickname'] }}</span>
                            <span class="text-[#C8922A] font-mono">{{ $t['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Target list --}}
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($alivePlayers as $p)
                    <button
                        wire:click="selectTarget('{{ $p['id'] }}')"
                        class="w-full px-4 py-3 bg-[#251E16] text-[#E8D9B5] rounded-lg hover:bg-[#3A3530] transition-colors text-left"
                    >
                        {{ $p['nickname'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
