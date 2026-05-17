<?php

namespace App\Http\Livewire;

use App\Events\SuspiciousAccessAttempt;
use App\Game\Engine\GameEngine;
use App\Models\GameState;
use App\Models\Player;
use App\Models\Room;
use App\Models\Vote;
use Livewire\Component;

// DO NOT write game_states.phase directly — use PhaseManager::transition()
class NarratorDashboard extends Component
{
    public Room $room;
    public GameState $state;
    public Player $player;
    public ?string $winnerFaction = null;

    public function mount(Room $room)
    {
        $requestPlayer = request()->get('_player');

        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $room->id) {
            if ($requestPlayer) {
                event(new SuspiciousAccessAttempt($requestPlayer, 'Non-narrator attempted narrator dashboard'));
            }
            $this->redirect(route('home'));
            return;
        }

        if ($room->status === 'waiting') {
            $this->redirect(route('lobby.narrator', $room));
            return;
        }

        $this->room = $room;
        $this->player = $requestPlayer;
        $this->state = $room->gameState;
    }

    public function advancePhase(string $toPhase)
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $this->room->id) {
            event(new SuspiciousAccessAttempt($requestPlayer ?? $this->player, 'Non-narrator attempted phase transition'));
            $this->redirect(route('home'));
            return;
        }

        try {
            $engine = app(GameEngine::class);

            if ($this->state->phase === 'night' && $toPhase === 'day') {
                $engine->resolveNight($this->state);
            } elseif ($this->state->phase === 'voting' && $toPhase === 'night') {
                $engine->resolveVote($this->state);
            } else {
                $engine->advancePhase($this->state, $toPhase);
            }

            $this->state = $this->state->fresh();
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function getListeners()
    {
        return [
            "echo-private:room.{$this->room->id},PhaseChanged" => '$refresh',
            "echo-private:room.{$this->room->id},PlayerEliminated" => '$refresh',
            "echo-private:narrator.{$this->room->id},VoteSubmitted" => '$refresh',
        ];
    }

    public function render()
    {
        $players = Player::where('room_id', $this->room->id)
            ->where('is_narrator', false)
            ->with('role')
            ->orderBy('created_at')
            ->get();

        $phase = $this->state->phase;
        $availableTransitions = $this->getTransitions($phase);

        $voteTally = [];
        $voteCount = 0;
        if ($phase === 'voting') {
            $votes = Vote::where('game_state_id', $this->state->id)->get();
            $voteCount = $votes->count();
            foreach ($votes as $v) {
                $voteTally[$v->target_id] = ($voteTally[$v->target_id] ?? 0) + 1;
            }
            arsort($voteTally);
            $eligibleCount = $players->where('is_alive', true)->where('voting_banned', false)->count();
        }

        $totalAlive = $players->where('is_alive', true)->count();

        return view('livewire.narrator-dashboard', [
            'players' => $players,
            'availableTransitions' => $availableTransitions,
            'voteTally' => $voteTally,
            'voteCount' => $voteCount,
            'totalAlive' => $totalAlive,
        ])->layout('layouts.app');
    }

    private function getTransitions(string $phase): array
    {
        return match ($phase) {
            'night' => ['day', 'finished'],
            'day' => ['voting'],
            'voting' => ['night', 'finished'],
            default => [],
        };
    }
}
