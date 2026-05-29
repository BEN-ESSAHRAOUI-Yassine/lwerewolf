<?php

namespace App\Livewire\Lobby;

use App\Game\Services\LobbyService;
use Livewire\Component;

class CreateRoom extends Component
{
    public string $nickname = '';

    public function submit(LobbyService $lobbyService)
    {
        $this->validate(['nickname' => 'required|string|max:30']);

        $locale = app()->getLocale();
        $room = $lobbyService->createRoom($this->nickname, $locale);

        $player = $room->host;

        cookie()->queue(cookie()->make('session_token', $player->session_token, 1440, '/', null, false, true));

        $this->dispatch('room-created', redirectUrl: route('lobby.narrator', $room->code));
    }

    public function render()
    {
        return view('livewire.lobby.create-room');
    }
}
