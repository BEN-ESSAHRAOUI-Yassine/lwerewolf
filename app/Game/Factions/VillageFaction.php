<?php

namespace App\Game\Factions;

use App\Models\GameState;
use App\Models\Player;
use Illuminate\Support\Collection;

class VillageFaction implements FactionInterface
{
    public function getKey(): string { return 'village'; }
    public function getName(string $locale): string { return __('ui.factions.village', [], $locale); }

    public function checkWin(GameState $state): bool
    {
        return !$this->getAlivePlayers($state)
            ->contains(fn (Player $p) => in_array($p->role?->key, [
                'werewolf', 'big_bad_wolf', 'accursed_wolf_father',
            ]) || ($p->role?->key === 'wolf_hound'
                && ($state->data['wolf_hound_choice'] ?? null) === 'werewolf'));
    }

    public function getWinners(GameState $state): Collection
    {
        return $this->getAlivePlayers($state)->filter(
            fn (Player $p) => $p->role && $p->role->faction === 'village'
        );
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
