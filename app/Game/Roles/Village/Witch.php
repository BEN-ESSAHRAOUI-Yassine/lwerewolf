<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class Witch extends BaseRole
{
    public function getKey(): string { return 'witch'; }
    public function getName(string $locale): string { return __('roles.witch.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return 10; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'save_potion', 'target' => 'single_player', 'once_per' => 'game', 'uses' => 1],
            ['key' => 'poison_potion', 'target' => 'single_player', 'once_per' => 'game', 'uses' => 1],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
