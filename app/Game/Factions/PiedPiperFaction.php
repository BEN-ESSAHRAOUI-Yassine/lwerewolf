<?php

namespace App\Game\Factions;

use App\Models\GameState;
use App\Models\Player;
use Illuminate\Support\Collection;

class PiedPiperFaction implements FactionInterface
{
    public function getKey(): string { return 'pied_piper'; }
    public function getName(string $locale): string { return __('ui.factions.pied_piper', [], $locale); }

    public function checkWin(GameState $state): bool
    {
        $data = $state->data ?? [];
        $enchanted = $data['enchanted_player_ids'] ?? [];
        if (empty($enchanted)) return false;

        $alive = $this->getAlivePlayers($state);

        foreach ($alive as $player) {
            if (!in_array($player->id, $enchanted)) {
                return false;
            }
        }

        return true;
    }

    public function getWinners(GameState $state): Collection
    {
        return Player::where('room_id', $state->room_id)
            ->where(function ($q) {
                $q->whereHas('role', fn ($r) => $r->where('key', 'pied_piper'));
            })
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
