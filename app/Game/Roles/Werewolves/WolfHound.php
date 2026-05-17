<?php

namespace App\Game\Roles\Werewolves;

use App\Game\Roles\BaseRole;

class WolfHound extends BaseRole
{
    public function getKey(): string { return 'wolf_hound'; }
    public function getName(string $locale): string { return __('roles.wolf_hound.name', [], $locale); }
    public function getFaction(): string { return 'werewolves'; }
    public function getNightOrder(): ?int { return 2; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'choose_side', 'target' => 'none', 'once_per' => 'game', 'night' => 1],
        ];
    }
    public function getWinCondition(): string { return 'parity with village'; }
}
