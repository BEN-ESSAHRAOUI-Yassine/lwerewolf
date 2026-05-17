<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Villager extends BaseRole
{
    public function getKey(): string { return 'villager'; }
    public function getName(string $locale): string { return __('roles.villager.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array { return []; }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
