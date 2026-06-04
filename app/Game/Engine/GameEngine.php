<?php

namespace App\Game\Engine;

use App\Events\GameFinished;
use App\Events\PlayerEliminated;
use App\Game\Services\RoleAssignmentService;
use App\Models\GameState;
use App\Models\Player;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

// DO NOT write game_states.phase directly — delegate to PhaseManager::transition()
class GameEngine
{
    public function __construct(
        private PhaseManager $phaseManager,
        private RoleAssignmentService $roleAssignment,
        private WinConditionChecker $winChecker,
        private ActionResolver $actionResolver,
    ) {}

    public function startGame(Room $room): GameState
    {
        return DB::transaction(function () use ($room) {
            $state = $this->roleAssignment->assign($room);

            return $state->fresh();
        });
    }

    public function advancePhase(GameState $state, string $toPhase): void
    {
        $this->phaseManager->transition($state, $toPhase);
    }

    public function resolveVote(GameState $state): ?\App\Game\Factions\FactionInterface
    {
        $service = app(\App\Game\Services\VotingService::class);
        $winner = $service->resolve($state);

        if ($winner) {
            $this->endGame($state, $winner);
        } else {
            $data = $state->data ?? [];
            $secondVote = $data['second_vote_triggered'] ?? false;
            if ($secondVote) {
                $data['second_vote_triggered'] = false;
                $state->data = $data;
                $state->save();
                $this->phaseManager->transition($state, 'day');
            } else {
                $this->phaseManager->transition($state, 'night');
            }
        }

        return $winner;
    }

    public function resolveNight(GameState $state): void
    {
        $this->actionResolver->resolve($state);

        $winner = $this->winChecker->check($state);
        if ($winner) {
            $this->endGame($state, $winner);
        } else {
            $this->phaseManager->transition($state, 'day');
        }
    }

    public function eliminatePlayer(Player $player, GameState $state): ?\App\Game\Factions\FactionInterface
    {
        return DB::transaction(function () use ($player, $state) {
            $player->is_alive = false;
            $player->save();

            event(new PlayerEliminated($player));

            $winner = $this->winChecker->check($state);

            if ($winner) {
                $this->endGame($state, $winner);
            }

            return $winner;
        });
    }

    public function endGame(GameState $state, \App\Game\Factions\FactionInterface $winner): void
    {
        DB::transaction(function () use ($state, $winner) {
            $data = $state->data;
            $data['winning_faction'] = $winner->getKey();
            $state->data = $data;
            $state->save();

            $this->phaseManager->transition($state, 'finished');

            $winners = $winner->getWinners($state);

            $room = $state->room;
            $room->status = 'finished';
            $room->save();

            event(new GameFinished(
                $state,
                $winner->getKey(),
                $winners->pluck('id')->toArray()
            ));
        });
    }
}
