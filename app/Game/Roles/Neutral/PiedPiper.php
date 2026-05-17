<?php

namespace App\Game\Roles\Neutral;

use App\Game\Roles\BaseRole;

class PiedPiper extends BaseRole
{
    public function getKey(): string { return 'pied_piper'; }
    public function getName(string $locale): string { return __('roles.pied_piper.name', [], $locale); }
    public function getFaction(): string { return 'pied_piper'; }
    public function getNightOrder(): ?int { return 11; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'enchant', 'target' => 'single_player', 'once_per' => 'night'],
        ];
    }
    public function getWinCondition(): string { return 'all living players enchanted'; }
}
