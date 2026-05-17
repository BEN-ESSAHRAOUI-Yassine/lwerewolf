<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Fox extends BaseRole
{
    public function getKey(): string { return 'fox'; }
    public function getName(string $locale): string { return __('roles.fox.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 12; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'sniff', 'target' => 'three_adjacent_players', 'once_per' => 'night'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
