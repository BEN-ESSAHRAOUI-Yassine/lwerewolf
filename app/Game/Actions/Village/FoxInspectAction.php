<?php

namespace App\Game\Actions\Village;

use App\Events\FoxResultReady;
use App\Game\Actions\BaseAction;
use App\Models\GameState;
use App\Models\Player;

class FoxInspectAction extends BaseAction
{
    public function getPriority(): int { return 10; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        $data = $state->data ?? [];
        return !empty($data['fox_ability_active']);
    }

    public function resolve(GameState $state): void
    {
        $metadata = $this->record->metadata ?? [];
        $targetIds = $metadata['target_ids'] ?? ($this->target ? [$this->target->id] : []);

        $alivePlayers = Player::whereIn('id', $targetIds)
            ->where('is_alive', true)
            ->with('role')
            ->get();

        $werewolfFound = false;
        foreach ($alivePlayers as $p) {
            if ($p->role && in_array($p->role->key, [
                'werewolf', 'big_bad_wolf', 'accursed_wolf_father',
            ])) {
                $werewolfFound = true;
                break;
            }
            if ($p->role && $p->role->key === 'wolf_hound') {
                $data = $state->data ?? [];
                $choice = $data['wolf_hound_choice'] ?? null;
                if ($choice === 'werewolf') {
                    $werewolfFound = true;
                    break;
                }
            }
            if ($p->role && $p->role->key === 'white_werewolf') {
                $werewolfFound = true;
                break;
            }
        }

        if (!$werewolfFound) {
            $data = $state->data ?? [];
            $data['fox_ability_active'] = false;
            $state->data = $data;
            $state->save();
        }

        $data = $state->data ?? [];
        $data['fox_results'][$this->record->player_id] = [
            'werewolf_found' => $werewolfFound,
        ];
        $state->data = $data;
        $state->save();

        event(new FoxResultReady($this->record->player, $werewolfFound));
    }
}
