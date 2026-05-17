<?php

namespace App\Http\Livewire;

use App\Events\SuspiciousAccessAttempt;
use App\Models\Player;
use App\Models\Room;
use Livewire\Component;

class PlayerGameView extends Component
{
    public Room $room;
    public Player $player;
    public bool $inLobby = false;

    public function mount(Room $room)
    {
        $requestPlayer = request()->get('_player');

        if (!$requestPlayer || $requestPlayer->room_id !== $room->id || $requestPlayer->is_narrator) {
            if ($requestPlayer) {
                event(new SuspiciousAccessAttempt($requestPlayer, 'Non-player attempted game view'));
            }
            $this->redirect(route('home'));
            return;
        }

        if ($room->status === 'waiting') {
            $this->redirect(route('lobby.player', $room));
            return;
        }

        $this->room = $room;
        $this->player = $requestPlayer->fresh(['role']);
    }

    public function getListeners()
    {
        return [
            "echo-private:room.{$this->room->id},PhaseChanged" => '$refresh',
            "echo-private:room.{$this->room->id},PlayerEliminated" => '$refresh',
            "echo-private:player.{$this->player->id},RoleAssigned" => '$refresh',
        ];
    }

    public function render()
    {
        $state = $this->room->gameState;

        $players = collect();
        if ($state && $state->phase === 'finished') {
            $players = Player::where('room_id', $this->room->id)
                ->where('is_narrator', false)
                ->with('role')
                ->orderBy('created_at')
                ->get();
        }

        return view('livewire.player-game-view', [
            'state' => $state,
            'players' => $players,
        ])->layout('layouts.app');
    }
}
