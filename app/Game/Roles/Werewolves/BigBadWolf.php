<?php

namespace App\Game\Roles\Werewolves;

use App\Game\Roles\BaseRole;

class BigBadWolf extends BaseRole
{
    public function getKey(): string { return 'big_bad_wolf'; }
    public function getName(string $locale): string { return __('roles.big_bad_wolf.name', [], $locale); }
    public function getFaction(): string { return 'werewolves'; }
    public function getNightOrder(): ?int { return 5; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'extra_kill', 'target' => 'single_player', 'once_per' => 'night', 'condition' => 'no_wolf_died'],
        ];
    }
    public function getWinCondition(): string { return 'parity with village'; }
}
