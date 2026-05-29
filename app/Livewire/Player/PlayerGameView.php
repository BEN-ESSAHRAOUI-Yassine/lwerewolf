<?php

namespace App\Livewire\Player;

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
        $roomId = $this->room->id;
        return [
            "echo-private:room.{$roomId},PhaseChanged" => 'onPhaseChanged',
            "echo-private:room.{$roomId},PlayerEliminated" => '$refresh',
            "echo-private:player.{$this->player->id},RoleAssigned" => '$refresh',
        ];
    }

    public function onPhaseChanged(array $payload)
    {
        $this->state = $this->room->gameState->fresh();
        $phase = $this->state->phase;
        $labels = [
            'night' => __('ui.phase.night'),
            'day' => __('ui.phase.day'),
            'voting' => __('ui.phase.voting'),
            'finished' => __('ui.phase.finished'),
        ];
        $classes = [
            'night' => 'phase-overlay phase-overlay-night',
            'day' => 'phase-overlay phase-overlay-day',
            'voting' => 'phase-overlay phase-overlay-voting',
            'finished' => 'phase-overlay phase-overlay-finished',
        ];
        $this->dispatch('transition-phase', label: $labels[$phase] ?? '', class: $classes[$phase] ?? '');
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

        return view('livewire.player.player-game-view', [
            'state' => $state,
            'players' => $players,
        ])->layout('layouts.app');
    }
}
