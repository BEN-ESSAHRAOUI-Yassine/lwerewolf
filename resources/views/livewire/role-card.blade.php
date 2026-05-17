<div
    class="relative w-64 h-96 rounded-xl cursor-pointer select-none"
    x-data="{ revealed: false }"
    x-on:mousedown="revealed = true"
    x-on:mouseup="revealed = false"
    x-on:mouseleave="revealed = false"
    x-on:touchstart="revealed = true"
    x-on:touchend="revealed = false"
>
    {{-- Masked face --}}
    <div
        x-show="!revealed"
        class="absolute inset-0 bg-gradient-to-br from-[#1A1510] to-[#0D0A07] border-2 border-[#C8922A]/30 rounded-xl flex items-center justify-center"
    >
        <div class="text-center">
            <div class="text-5xl mb-3 text-[#C8922A]/40">?</div>
            <p class="text-[#9A8A6A] text-sm tracking-widest uppercase">{{ __('ui.role.hold_to_reveal') }}</p>
        </div>
    </div>

    {{-- Revealed face --}}
    <div
        x-show="revealed"
        x-cloak
        class="absolute inset-0 bg-gradient-to-br from-[#2A2015] to-[#1A1510] border-2 border-[#C8922A] rounded-xl p-6 flex flex-col items-center justify-center"
    >
        <p class="text-[#9A8A6A] text-xs uppercase tracking-widest mb-2">{{ __("ui.factions.{$role->faction}") }}</p>
        <h2 class="text-[#E8D9B5] text-2xl font-bold text-center mb-4">{{ __("roles.{$role->key}.name") }}</h2>
        <p class="text-[#9A8A6A] text-sm text-center">{{ __("roles.{$role->key}.description") }}</p>
        @if($role->night_order !== null)
            <div class="mt-4 text-[#C8922A] text-xs">
                {{ __('ui.role.night_order') }}: {{ $role->night_order }}
            </div>
        @endif
    </div>
</div>
