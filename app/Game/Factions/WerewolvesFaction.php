<?php

namespace App\Game\Factions;

use App\Models\GameState;
use App\Models\Player;
use Illuminate\Support\Collection;

class WerewolvesFaction implements FactionInterface
{
    public function getKey(): string { return 'werewolves'; }
    public function getName(string $locale): string { return __('ui.factions.werewolves', [], $locale); }

    public function checkWin(GameState $state): bool
    {
        $alive = $this->getAlivePlayers($state);
        $data = $state->data ?? [];
        $round = $state->round;

        $werewolfCount = $alive->filter(fn (Player $p) => $p->role && in_array($p->role->key, [
            'werewolf', 'big_bad_wolf', 'accursed_wolf_father',
        ]) || ($p->role->key === 'wolf_hound'
            && ($data['wolf_hound_choice'] ?? null) === 'werewolf'))->count();

        $villageAligned = $alive->filter(fn (Player $p) => $p->role && (
            $p->role->faction === 'village'
            || ($p->role->key === 'wolf_hound'
                && ($data['wolf_hound_choice'] ?? null) === 'village')
            || ($p->role->key === 'angel' && $round > 1)
        ))->count();

        return $werewolfCount > 0 && $werewolfCount >= $villageAligned;
    }

    public function getWinners(GameState $state): Collection
    {
        return $this->getAlivePlayers($state)->filter(
            fn (Player $p) => $p->role && $p->role->faction === 'werewolves'
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
