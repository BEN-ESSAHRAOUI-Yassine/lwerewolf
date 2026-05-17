<?php

namespace App\Game\Actions\Werewolves;

use App\Game\Actions\BaseAction;
use App\Models\GameState;

class WerewolfKillAction extends BaseAction
{
    public function getPriority(): int { return 3; }

    public function resolve(GameState $state): void
    {
        $data = $state->data ?? [];
        $data['werewolf_kill_target_id'] = $this->target?->id;
        $state->data = $data;
        $state->save();
    }
}
