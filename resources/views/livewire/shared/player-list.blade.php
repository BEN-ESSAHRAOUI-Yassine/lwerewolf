<div wire:poll.3s>
    <div class="space-y-2">
        @foreach($players as $p)
            <div class="flex items-center gap-3 px-4 py-3 bg-[#1A1510] rounded-lg border border-[#251E16]">
                <div class="w-2 h-2 rounded-full bg-[#3A6B3A]"></div>
                <span class="text-[#E8D9B5]">{{ $p['nickname'] }}</span>
            </div>
        @endforeach
    </div>

    @if(count($players) === 0)
        <p class="text-[#9A8A6A] text-center py-8">{{ __('ui.lobby.no_players_yet') }}</p>
    @endif
</div>
