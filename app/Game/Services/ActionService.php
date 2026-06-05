<?php

namespace App\Game\Services;

use App\Events\NightActionSubmitted;
use App\Models\GameState;
use App\Models\NightAction;
use App\Models\Player;
use App\Models\Role;

class ActionService
{
    public function submit(Player $player, array $data): ?NightAction
    {
        if ($player->is_narrator) abort(403);
        if (!$player->is_alive) abort(403);

        $room = $player->room;
        $state = $room->gameState;
        if (!$state || $state->phase !== 'night') abort(403);

        $actionType = $data['action_type'];
        $role = $player->role;
        if (!$role) abort(403);

        if (!$this->roleCanPerformAction($role, $actionType)) abort(403);

        $alreadySubmitted = NightAction::where('game_state_id', $state->id)
            ->where('player_id', $player->id)
            ->where('action_type', $actionType)
            ->whereNull('resolved_at')
            ->exists();

        if ($alreadySubmitted) return null;

        $target = $data['target_id'] ? Player::find($data['target_id']) : null;

        $action = NightAction::create([
            'game_state_id' => $state->id,
            'player_id' => $player->id,
            'action_type' => $actionType,
            'target_id' => $data['target_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $stateData = $state->data;
        $history = $stateData['action_history'] ?? [];
        $history[] = [
            'round' => $state->round,
            'player_id' => $player->id,
            'player_nickname' => $player->nickname,
            'role_key' => $role->key,
            'action_type' => $actionType,
            'target_nickname' => $target?->nickname,
            'timestamp' => now()->toIso8601String(),
        ];
        $stateData['action_history'] = $history;
        $state->data = $stateData;
        $state->save();

        event(new NightActionSubmitted($action));

        return $action;
    }

    public function getActionsForRound(GameState $state): \Illuminate\Support\Collection
    {
        return NightAction::where('game_state_id', $state->id)
            ->whereNull('resolved_at')
            ->with(['player', 'target'])
            ->get();
    }

    private function roleCanPerformAction(Role $role, string $actionType): bool
    {
        $map = [
            'werewolf' => ['kill'],
            'big_bad_wolf' => ['extra_kill'],
            'accursed_wolf_father' => ['convert'],
            'white_werewolf' => ['solo_kill'],
            'bodyguard' => ['protect'],
            'seer' => ['inspect'],
            'witch' => ['save', 'poison'],
            'pied_piper' => ['enchant'],
            'fox' => ['sniff'],
            'cupid' => ['link_lovers'],
            'wolf_hound' => ['choose_side'],
        ];

        $allowed = $map[$role->key] ?? [];

        return in_array($actionType, $allowed, true);
    }
}
