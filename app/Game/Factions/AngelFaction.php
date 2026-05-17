<?php

namespace App\Game\Factions;

use App\Models\GameState;
use App\Models\Player;
use Illuminate\Support\Collection;

class AngelFaction implements FactionInterface
{
    public function getKey(): string { return 'angel'; }
    public function getName(string $locale): string { return __('ui.factions.angel', [], $locale); }

    public function checkWin(GameState $state): bool
    {
        if ($state->round !== 1) return false;

        $data = $state->data ?? [];
        if (!isset($data['angel_eliminated_by_vote']) || !$data['angel_eliminated_by_vote']) {
            return false;
        }

        return true;
    }

    public function getWinners(GameState $state): Collection
    {
        return Player::where('room_id', $state->room_id)
            ->whereHas('role', fn ($q) => $q->where('key', 'angel'))
            ->where('is_narrator', false)
            ->get();
    }
}
