<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class StutteringJudge extends BaseRole
{
    public function getKey(): string { return 'stuttering_judge'; }
    public function getName(string $locale): string { return __('roles.stuttering_judge.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'second_vote', 'once_per' => 'game'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
