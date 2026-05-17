<?php

namespace App\Game\Factions;

use App\Models\CoupleBond;
use App\Models\GameState;
use App\Models\Player;
use Illuminate\Support\Collection;

class LoversFaction implements FactionInterface
{
    public function getKey(): string { return 'lovers'; }
    public function getName(string $locale): string { return __('ui.factions.lovers', [], $locale); }

    public function checkWin(GameState $state): bool
    {
        $bonds = CoupleBond::where('game_state_id', $state->id)->get();
        if ($bonds->isEmpty()) return false;

        $alive = $this->getAlivePlayers($state);
        if ($alive->count() !== 2) return false;

        $playerIds = $alive->pluck('id')->toArray();

        foreach ($bonds as $bond) {
            if (in_array($bond->player_id, $playerIds)
                && in_array($bond->partner_id, $playerIds)
            ) {
                $p1 = $alive->firstWhere('id', $bond->player_id);
                $p2 = $alive->firstWhere('id', $bond->partner_id);
                if ($p1 && $p2
                    && $p1->role && $p2->role
                    && $p1->role->faction !== $p2->role->faction
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getWinners(GameState $state): Collection
    {
        return $this->getAlivePlayers($state);
    }

    private function getAlivePlayers(GameState $state): Collection
    {
        return Player::where('room_id', $state->room_id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->with('role')
            ->get();
    }
}
