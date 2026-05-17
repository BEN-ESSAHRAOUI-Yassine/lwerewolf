<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Cupid extends BaseRole
{
    public function getKey(): string { return 'cupid'; }
    public function getName(string $locale): string { return __('roles.cupid.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 0; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'link', 'target' => 'two_players', 'once_per' => 'game', 'night' => 1],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
