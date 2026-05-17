<?php

namespace App\Game\Actions\Village;

use App\Game\Actions\BaseAction;
use App\Models\GameState;

class WitchSaveAction extends BaseAction
{
    public function getPriority(): int { return 7; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        $data = $state->data ?? [];
        return empty($data['witch_save_used']);
    }

    public function resolve(GameState $state): void
    {
        $data = $state->data ?? [];
        $data['witch_save_target_id'] = $this->target?->id;
        $data['witch_save_used'] = true;
        $state->data = $data;
        $state->save();
    }
}
