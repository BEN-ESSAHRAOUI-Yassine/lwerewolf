<?php

namespace App\Game\Actions\Village;

use App\Events\SeerResultReady;
use App\Game\Actions\BaseAction;
use App\Models\GameState;

class SeerInspectAction extends BaseAction
{
    public function getPriority(): int { return 11; }

    public function resolve(GameState $state): void
    {
        if (!$this->target) return;

        $faction = $this->target->role?->faction ?? 'unknown';

        $data = $state->data ?? [];
        $data['seer_results'][$this->record->player_id] = [
            'target_nickname' => $this->target->nickname,
            'faction' => $faction,
        ];
        $state->data = $data;
        $state->save();

        event(new SeerResultReady(
            $this->record->player,
            $this->target->nickname,
            $faction
        ));
    }
}
