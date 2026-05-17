<?php

namespace App\Game\Actions\Village;

use App\Game\Actions\BaseAction;
use App\Models\GameState;

class BodyguardProtectAction extends BaseAction
{
    public function getPriority(): int { return 2; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        $data = $state->data ?? [];
        $lastProtected = $data['bodyguard_last_protected_id'] ?? null;
        return $lastProtected !== $this->target?->id;
    }

    public function resolve(GameState $state): void
    {
        $data = $state->data ?? [];
        $data['bodyguard_protected_id'] = $this->target?->id;
        $data['bodyguard_last_protected_id'] = $this->target?->id;
        $state->data = $data;
        $state->save();
    }
}
