<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class BearTamer extends BaseRole
{
    public function getKey(): string { return 'bear_tamer'; }
    public function getName(string $locale): string { return __('roles.bear_tamer.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 13; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'bear_growl', 'passive' => true],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
