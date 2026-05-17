<?php

namespace App\Game\Actions\Village;

use App\Game\Actions\BaseAction;
use App\Models\CoupleBond;
use App\Models\GameState;

class CupidLinkAction extends BaseAction
{
    public function getPriority(): int { return 1; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        if ($state->round !== 1) return false;
        $exists = CoupleBond::where('game_state_id', $state->id)->exists();
        return !$exists;
    }

    public function resolve(GameState $state): void
    {
        $metadata = $this->record->metadata ?? [];
        $partnerId = $metadata['partner_id'] ?? null;

        if (!$partnerId || !$this->target) return;

        CoupleBond::create([
            'game_state_id' => $state->id,
            'player_id' => $this->target->id,
            'partner_id' => $partnerId,
        ]);
    }

    public function getTargetNickname(): string
    {
        return $this->target?->nickname ?? '';
    }
}
