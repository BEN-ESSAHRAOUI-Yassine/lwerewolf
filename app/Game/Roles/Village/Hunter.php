<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Hunter extends BaseRole
{
    public function getKey(): string { return 'hunter'; }
    public function getName(string $locale): string { return __('roles.hunter.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'last_shot', 'trigger' => 'on_elimination'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
