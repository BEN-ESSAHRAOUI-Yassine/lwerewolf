<?php

namespace App\Game\Actions\Neutral;

use App\Game\Actions\BaseAction;
use App\Models\GameState;

class PiedPiperEnchantAction extends BaseAction
{
    public function getPriority(): int { return 9; }

    public function resolve(GameState $state): void
    {
        if (!$this->target) return;

        $data = $state->data ?? [];
        $enchanted = $data['enchanted_player_ids'] ?? [];

        if (!in_array($this->target->id, $enchanted)) {
            $enchanted[] = $this->target->id;
        }

        $data['enchanted_player_ids'] = $enchanted;
        $state->data = $data;
        $state->save();
    }
}
