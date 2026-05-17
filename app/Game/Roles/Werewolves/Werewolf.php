<?php

namespace App\Game\Roles\Werewolves;

use App\Game\Roles\BaseRole;

class Werewolf extends BaseRole
{
    public function getKey(): string { return 'werewolf'; }
    public function getName(string $locale): string { return __('roles.werewolf.name', [], $locale); }
    public function getFaction(): string { return 'werewolves'; }
    public function getNightOrder(): ?int { return 4; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'group_kill', 'target' => 'single_player', 'once_per' => 'night'],
        ];
    }
    public function getWinCondition(): string { return 'parity with village'; }
}
