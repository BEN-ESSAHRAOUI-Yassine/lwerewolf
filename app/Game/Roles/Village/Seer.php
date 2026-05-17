<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Seer extends BaseRole
{
    public function getKey(): string { return 'seer'; }
    public function getName(string $locale): string { return __('roles.seer.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 9; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'inspect', 'target' => 'single_player', 'result' => 'faction', 'private' => true, 'once_per' => 'night'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
