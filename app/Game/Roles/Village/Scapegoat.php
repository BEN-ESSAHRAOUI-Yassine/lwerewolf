<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Scapegoat extends BaseRole
{
    public function getKey(): string { return 'scapegoat'; }
    public function getName(string $locale): string { return __('roles.scapegoat.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'sacrifice', 'trigger' => 'on_tie'],
            ['key' => 'last_decree', 'trigger' => 'after_sacrifice'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
