<?php

namespace App\Game\Actions;

use App\Models\GameState;
use App\Models\NightAction;
use App\Models\Player;

abstract class BaseAction implements ActionInterface
{
    protected NightAction $record;
    protected ?Player $target;

    public function __construct(NightAction $record)
    {
        $this->record = $record;
        $this->target = $record->target_id ? Player::find($record->target_id) : null;
    }

    public function getTarget(): ?Player
    {
        return $this->target;
    }

    public function getActingRole(): string
    {
        return $this->record->action_type;
    }

    public function isValid(GameState $state): bool
    {
        if ($state->phase !== 'night') return false;

        $player = $this->record->player;
        if (!$player || !$player->is_alive) return false;

        if ($this->target && !$this->target->is_alive) return false;

        return true;
    }

    protected function getAlivePlayers(GameState $state)
    {
        return Player::where('room_id', $state->room_id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->get();
    }
}
