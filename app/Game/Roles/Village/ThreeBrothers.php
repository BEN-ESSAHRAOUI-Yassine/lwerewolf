<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class ThreeBrothers extends BaseRole
{
    public function getKey(): string { return 'three_brothers'; }
    public function getName(string $locale): string { return __('roles.three_brothers.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'kinship', 'passive' => true],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
