<?php

namespace App\Game\Factions;

use App\Models\GameState;
use App\Models\Player;
use Illuminate\Support\Collection;

class WhiteWerewolfFaction implements FactionInterface
{
    public function getKey(): string { return 'white_werewolf'; }
    public function getName(string $locale): string { return __('ui.factions.white_werewolf', [], $locale); }

    public function checkWin(GameState $state): bool
    {
        $alive = $this->getAlivePlayers($state);
        if ($alive->count() !== 1) return false;

        $lastPlayer = $alive->first();

        return $lastPlayer && $lastPlayer->role && $lastPlayer->role->key === 'white_werewolf';
    }

    public function getWinners(GameState $state): Collection
    {
        return Player::where('room_id', $state->room_id)
            ->whereHas('role', fn ($q) => $q->where('key', 'white_werewolf'))
            ->where('is_narrator', false)
            ->get();
    }

    private function getAlivePlayers(GameState $state): Collection
    {
        return Player::where('room_id', $state->room_id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->get();
    }
}
