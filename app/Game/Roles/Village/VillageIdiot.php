<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class VillageIdiot extends BaseRole
{
    public function getKey(): string { return 'village_idiot'; }
    public function getName(string $locale): string { return __('roles.village_idiot.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'revealed_innocence', 'trigger' => 'on_vote_elimination'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
