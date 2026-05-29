<?php

namespace App\Livewire\Shared;

use App\Models\Player;
use App\Models\Room;
use Livewire\Component;

class PlayerList extends Component
{
    public Room $room;
    public array $players = [];

    public function mount(Room $room)
    {
        $this->room = $room;
        $this->refreshPlayers();
    }

    public function refreshPlayers()
    {
        $this->players = Player::where('room_id', $this->room->id)
            ->where('is_narrator', false)
            ->orderBy('created_at')
            ->get(['id', 'nickname', 'is_host', 'is_narrator', 'created_at'])
            ->toArray();
    }

    public function getListeners()
    {
        return [
            "echo-private:room.{$this->room->id},PlayerJoined" => 'refreshPlayers',
            "echo-private:room.{$this->room->id},PlayerLeft" => 'refreshPlayers',
        ];
    }

    public function render()
    {
        $this->refreshPlayers();
        return view('livewire.shared.player-list');
    }
}
