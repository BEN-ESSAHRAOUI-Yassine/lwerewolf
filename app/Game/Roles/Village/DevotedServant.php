<?php

namespace App\Game\Roles\Village;

use App\Game\Roles\BaseRole;

class DevotedServant extends BaseRole
{
    public function getKey(): string { return 'devoted_servant'; }
    public function getName(string $locale): string { return __('roles.devoted_servant.name', [], $locale); }
    public function getFaction(): string { return 'village'; }
    public function getNightOrder(): ?int { return null; }
    public function getAbilities(): array
    {
        return [
            ['key' => 'sacrifice', 'trigger' => 'on_vote_elimination'],
        ];
    }
    public function getWinCondition(): string { return 'all werewolves eliminated'; }
}
