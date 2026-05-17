<?php

namespace App\Game\Roles\Werewolves;

use App\Game\Roles\BaseRole;

class WhiteWerewolf extends BaseRole
{
    public function getKey(): string { return 'white_werewolf'; }
    public function getName(string $locale): string { return __('roles.white_werewolf.name', [], $locale); }
    public function getFaction(): string { return 'werewolves'; }
    public function getNightOrder(): ?int { return 6; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'solo_kill', 'target' => 'single_player', 'cadence' => 'every_other_night'],
        ];
    }
    public function getWinCondition(): string { return 'last player standing'; }
}
