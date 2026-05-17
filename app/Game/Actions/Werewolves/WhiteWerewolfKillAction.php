<?php

namespace App\Game\Actions\Werewolves;

use App\Game\Actions\BaseAction;
use App\Models\GameState;

class WhiteWerewolfKillAction extends BaseAction
{
    public function getPriority(): int { return 6; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        $data = $state->data ?? [];
        $soloNight = $data['white_werewolf_solo_night'] ?? 0;
        $round = $state->round;
        return $soloNight < $round && ($round % 2 === 0);
    }

    public function resolve(GameState $state): void
    {
        $data = $state->data ?? [];
        $data['white_werewolf_solo_target_id'] = $this->target?->id;
        $data['white_werewolf_solo_night'] = $state->round;
        $state->data = $data;
        $state->save();
    }
}
