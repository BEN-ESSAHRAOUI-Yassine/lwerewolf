<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Bodyguard extends BaseRole
{
    public function getKey(): string { return 'bodyguard'; }
    public function getName(string $locale): string { return __('roles.bodyguard.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 7; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'protect', 'target' => 'single_player', 'once_per' => 'night'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
