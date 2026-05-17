<?php

namespace App\Game\Actions\Werewolves;

use App\Game\Actions\BaseAction;
use App\Models\GameState;
use App\Models\Player;

class BigBadWolfKillAction extends BaseAction
{
    public function getPriority(): int { return 4; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        $anyWolfDead = Player::where('room_id', $state->room_id)
            ->where('is_alive', false)
            ->where('is_narrator', false)
            ->whereHas('role', fn ($q) => $q->whereIn('key', ['werewolf', 'big_bad_wolf', 'accursed_wolf_father']))
            ->exists();
        return !$anyWolfDead;
    }

    public function resolve(GameState $state): void
    {
        $data = $state->data ?? [];
        $data['big_bad_wolf_target_id'] = $this->target?->id;
        $state->data = $data;
        $state->save();
    }
}
