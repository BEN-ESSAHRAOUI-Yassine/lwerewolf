<?php

namespace App\Livewire\Player;

use App\Events\SuspiciousAccessAttempt;
use App\Models\Player;
use App\Models\Room;
use Livewire\Component;

class PlayerLobby extends Component
{
    public Room $room;
    public Player $player;
    public array $players = [];

    public function mount(Room $room)
    {
        $p = request()->get('_player');

        if (!$p || $p->room_id !== $room->id || $p->is_narrator) {
            if ($p) {
                event(new SuspiciousAccessAttempt($p, 'Unauthorized access to player lobby'));
            }
            $this->redirect(route('home'));
            return;
        }

        $this->room = $room;
        $this->player = $p;
        $this->refreshPlayers();
    }

    public function refreshPlayers()
    {
        $this->players = Player::where('room_id', $this->room->id)
            ->where('is_narrator', false)
            ->orderBy('created_at')
            ->get(['id', 'nickname', 'is_host'])
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
        return view('livewire.player.player-lobby');
    }
}
