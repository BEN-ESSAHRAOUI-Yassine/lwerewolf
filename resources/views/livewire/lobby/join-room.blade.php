<div class="min-h-screen flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-sm mx-auto">
        <h1 class="font-serif text-3xl text-[#E8D9B5] mb-8 text-center">{{ __('ui.lobby.join_room') }}</h1>

        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="code" class="block text-sm text-[#9A8A6A] mb-2">{{ __('ui.lobby.room_code') }}</label>
                <input
                    wire:model="code"
                    type="text"
                    id="code"
                    maxlength="6"
                    class="w-full px-4 py-3 bg-[#1A1510] border border-[#251E16] text-[#E8D9B5] rounded-lg focus:outline-none focus:border-[#C8922A] transition-colors text-center text-2xl tracking-[0.5em] uppercase"
                    placeholder="{{ __('ui.lobby.code_placeholder') }}"
                >
                @error('code') <p class="text-[#8B2020] text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nickname" class="block text-sm text-[#9A8A6A] mb-2">{{ __('ui.lobby.your_nickname') }}</label>
                <input
                    wire:model="nickname"
                    type="text"
                    id="nickname"
                    maxlength="30"
                    class="w-full px-4 py-3 bg-[#1A1510] border border-[#251E16] text-[#E8D9B5] rounded-lg focus:outline-none focus:border-[#C8922A] transition-colors"
                    placeholder="{{ __('ui.lobby.nickname_placeholder') }}"
                >
                @error('nickname') <p class="text-[#8B2020] text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full py-3 px-6 bg-[#C8922A] text-[#0D0D0D] font-semibold rounded-lg hover:bg-[#D4A235] transition-colors duration-200">
                {{ __('ui.button.join_room') }}
            </button>
        </form>
    </div>

    @script
        <script>
            $wire.on('room-joined', (event) => {
                setTimeout(() => {
                    window.location.href = event.redirectUrl;
                }, 100);
            });
        </script>
    @endscript
</div>
