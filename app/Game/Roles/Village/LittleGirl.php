<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class LittleGirl extends BaseRole
{
    public function getKey(): string { return 'little_girl'; }
    public function getName(string $locale): string { return __('roles.little_girl.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 8; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'spy', 'passive' => true],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
