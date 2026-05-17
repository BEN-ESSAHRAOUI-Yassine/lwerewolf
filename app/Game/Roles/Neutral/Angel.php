<?php

namespace App\Game\Roles\Neutral;

use App\Game\Roles\BaseRole;

class Angel extends BaseRole
{
    public function getKey(): string { return 'angel'; }
    public function getName(string $locale): string { return __('roles.angel.name', [], $locale); }
    public function getFaction(): string { return 'angel'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'divine_favor', 'trigger' => 'on_vote_elimination_in_round_1'],
        ];
    }
    public function getWinCondition(): string { return 'eliminated by vote in round 1'; }
}
