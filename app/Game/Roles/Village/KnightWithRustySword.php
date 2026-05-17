<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class KnightWithRustySword extends BaseRole
{
    public function getKey(): string { return 'knight_with_rusty_sword'; }
    public function getName(string $locale): string { return __('roles.knight_with_rusty_sword.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'rusty_wound', 'trigger' => 'on_werewolf_kill'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
