<div class="bg-[#1A1510] border border-[#251E16] rounded-xl p-6 w-full max-w-md"
     x-data="{ revealed: false }">
    @if($submitted && $submittedAction)
        {{-- Real action submitted --}}
        <div class="text-center"
             x-on:mousedown="revealed = true"
             x-on:mouseup="revealed = false"
             x-on:mouseleave="revealed = false"
             x-on:touchstart="revealed = true"
             x-on:touchend="revealed = false"
        >
            <div x-show="!revealed" class="py-4">
                <div class="text-3xl mb-2 text-[#C8922A]/40">&#10003;</div>
                <p class="text-[#9A8A6A]">{{ __('ui.action.submitted') }}</p>
            </div>
            <div x-show="revealed" x-cloak class="py-4">
                <p class="text-[#9A8A6A] text-xs uppercase tracking-widest">{{ __("roles.{$role->key}.name") }}</p>
                <p class="text-[#E8D9B5] text-lg mt-2">{{ __("ui.action.{$submittedAction->action_type}") }}</p>
                @if($submittedAction->target)
                    <p class="text-[#C8922A] mt-1">{{ $submittedAction->target->nickname }}</p>
                @endif
            </div>
        </div>
    @elseif($submitted && $isDecoy)
        {{-- Decoy submitted --}}
        <div class="text-center"
             x-on:mousedown="revealed = true"
             x-on:mouseup="revealed = false"
             x-on:mouseleave="revealed = false"
             x-on:touchstart="revealed = true"
             x-on:touchend="revealed = false"
        >
            <div x-show="!revealed" class="py-4">
                <div class="text-3xl mb-2 text-[#C8922A]/40">&#10003;</div>
                <p class="text-[#9A8A6A]">{{ __('ui.action.submitted') }}</p>
            </div>
            <div x-show="revealed" x-cloak class="py-4">
                @php $targetName = collect($alivePlayers)->firstWhere('id', $selectedTargetId)['nickname'] ?? ''; @endphp
                <p class="text-[#9A8A6A] text-xs uppercase tracking-widest">{{ __('ui.action.decoy_submitted') }}</p>
                <p class="text-[#C8922A] mt-1">{{ $targetName }}</p>
            </div>
        </div>
    @elseif($confirming && $selectedTargetId)
        <div class="text-center">
            <p class="text-[#9A8A6A] mb-4">{{ __('ui.action.confirm_action') }}</p>
            @php $targetName = collect($alivePlayers)->firstWhere('id', $selectedTargetId)['nickname'] ?? ''; @endphp
            <p class="text-[#E8D9B5] text-xl mb-6">{{ $targetName }}</p>
            <div class="flex gap-4 justify-center">
                <button wire:click="cancelSelection" class="px-6 py-2 bg-[#251E16] text-[#9A8A6A] rounded-lg hover:bg-[#3A3530]">{{ __('ui.button.cancel') }}</button>
                <button wire:click="confirmSubmit" class="px-6 py-2 bg-[#3A6B3A] text-[#E8D9B5] rounded-lg hover:bg-[#4A7B4A]">{{ __('ui.button.confirm') }}</button>
            </div>
        </div>
    @else
        <div class="text-center">
            <p class="text-[#9A8A6A] text-sm mb-4">{{ $isDecoy ? __('ui.action.decoy_select') : __("ui.roles.{$role->key}.action_prompt") }}</p>
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
