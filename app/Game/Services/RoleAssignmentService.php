<?php

namespace App\Game\Services;

use App\Events\GameStarted;
use App\Events\RoleAssigned;
use App\Models\GameState;
use App\Models\Player;
use App\Models\Role;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class RoleAssignmentService
{
    public function assign(Room $room): GameState
    {
        return DB::transaction(function () use ($room) {
            $settings = $room->settings ?? [];
            $roleCounts = $settings['role_counts'] ?? [];

            $players = Player::where('room_id', $room->id)
                ->where('is_narrator', false)
                ->get();

            $pool = [];
            foreach ($roleCounts as $key => $count) {
                $role = Role::where('key', $key)->firstOrFail();
                for ($i = 0; $i < $count; $i++) {
                    $pool[] = $role;
                }
            }

            shuffle($pool);
            $shuffledPlayers = $players->shuffle();

            foreach ($shuffledPlayers as $i => $player) {
                $role = $pool[$i] ?? null;
                if (!$role) break;
                $player->role_id = $role->id;
                $player->save();
            }

            $room->status = 'playing';
            $room->save();

            $seatOrder = $shuffledPlayers->pluck('id')->toArray();

            // DO NOT write game_states.phase directly — use PhaseManager::transition()
            // This creates at phase='waiting'; GameEngine transitions to 'night' after
            $state = GameState::create([
                'room_id' => $room->id,
                'phase' => 'waiting',
                'round' => 1,
                'data' => [
                    'seat_order' => $seatOrder,
                    'enchanted_player_ids' => [],
                    'wolf_father_used' => false,
                    'elder_first_attack_survived' => false,
                    'elder_abilities_disabled' => false,
                    'fox_ability_active' => true,
                    'bear_tamer_alive' => true,
                    'infected_werewolf_id' => null,
                    'wolf_hound_choice' => null,
                    'white_werewolf_solo_night' => 0,
                    'stuttering_judge_used' => false,
                    'second_vote_triggered' => false,
                    'pied_piper_eliminated' => false,
                    'vote_ban_next_round' => [],
                    'bodyguard_last_protected_id' => null,
                    'witch_save_used' => false,
                    'witch_poison_used' => false,
                    'devoted_servant_used' => false,
                    'knight_killed_by_werewolf' => false,
                    'players_ready' => [],
                ],
            ]);

            event(new GameStarted($room));

            foreach ($shuffledPlayers as $player) {
                event(new RoleAssigned($player->fresh(['role'])));
            }

            return $state;
        });
    }
}
