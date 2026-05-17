<?php

namespace App\Game\Roles\Werewolves;

use App\Game\Roles\BaseRole;

class AccursedWolfFather extends BaseRole
{
    public function getKey(): string { return 'accursed_wolf_father'; }
    public function getName(string $locale): string { return __('roles.accursed_wolf_father.name', [], $locale); }
    public function getFaction(): string { return 'werewolves'; }
    public function getNightOrder(): ?int { return 3; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'convert', 'target' => 'single_player', 'once_per' => 'game', 'replaces' => 'kill'],
        ];
    }
    public function getWinCondition(): string { return 'parity with village'; }
}
