<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Elder extends BaseRole
{
    public function getKey(): string { return 'elder'; }
    public function getName(string $locale): string { return __('roles.elder.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'resilience', 'passive' => true],
            ['key' => 'fragility', 'trigger' => 'on_vote_elimination'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
