<?php

namespace App\Game\Services;

use App\Events\PlayerJoined;
use App\Models\Player;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LobbyService
{
    public function createRoom(string $nickname, string $locale = 'en'): Room
    {
        $code = $this->generateUniqueCode();

        $room = Room::create([
            'code' => $code,
            'status' => 'waiting',
            'narration_mode' => 'human',
            'settings' => [
                'locale' => $locale,
                'role_counts' => [],
            ],
        ]);

        $player = $this->createHost($room, $nickname);
        $room->host_player_id = $player->id;
        $room->save();

        return $room->fresh();
    }

    public function joinRoom(Room $room, string $nickname, Request $request): Player
    {
        if ($room->status !== 'waiting') {
            throw ValidationException::withMessages(['code' => __('lobby.errors.game_started')]);
        }

        $existingToken = $request->cookie('session_token');
        if ($existingToken) {
            $existing = Player::where('session_token', $existingToken)->where('room_id', $room->id)->first();
            if ($existing) {
                throw ValidationException::withMessages(['code' => __('lobby.errors.already_joined')]);
            }
        }

        $duplicate = Player::where('room_id', $room->id)
            ->where('nickname', $nickname)
            ->where('is_narrator', false)
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['nickname' => __('lobby.errors.nickname_taken')]);
        }

        $count = Player::where('room_id', $room->id)->where('is_narrator', false)->count();
        if ($count >= 24) {
            throw ValidationException::withMessages(['code' => __('lobby.errors.room_full')]);
        }

        $sessionToken = (string) Str::uuid();

        $player = Player::create([
            'room_id' => $room->id,
            'nickname' => $nickname,
            'session_token' => $sessionToken,
            'is_alive' => true,
            'is_host' => false,
            'is_narrator' => false,
        ]);

        cookie()->queue(cookie()->make('session_token', $sessionToken, 1440, '/', null, false, true));

        event(new PlayerJoined($player));

        return $player;
    }

    public function assignNarrator(Room $room, Player $player): void
    {
        $player->is_narrator = true;
        $player->is_host = true;
        $player->role_id = null;
        $player->save();
    }

    public function validateGameStart(Room $room): array
    {
        $errors = [];

        $players = Player::where('room_id', $room->id)->where('is_narrator', false)->get();
        $playerCount = $players->count();
        $settings = $room->settings ?? [];
        $roleCounts = $settings['role_counts'] ?? [];

        if ($playerCount < 4) {
            $errors[] = __('lobby.validation.min_players');
        }

        $totalRoles = array_sum($roleCounts);
        if ($totalRoles !== $playerCount) {
            $errors[] = __('lobby.validation.role_count_mismatch');
        }

        $hasWerewolf = false;
        $hasVillage = false;

        foreach ($roleCounts as $roleKey => $count) {
            if ($count <= 0) continue;
            $role = \App\Models\Role::where('key', $roleKey)->first();
            if (!$role) continue;

            if ($role->faction === 'werewolves' || $role->key === 'werewolf') {
                $hasWerewolf = true;
            }
            if ($role->faction === 'village') {
                $hasVillage = true;
            }

            if ($roleKey === 'two_sisters' && $count !== 2) {
                $errors[] = __('lobby.validation.two_sisters_exact');
            }
            if ($roleKey === 'three_brothers' && $count !== 3) {
                $errors[] = __('lobby.validation.three_brothers_exact');
            }
            if (in_array($roleKey, ['white_werewolf', 'pied_piper', 'angel']) && $count > 1) {
                $errors[] = __('lobby.validation.solo_max_one');
            }
        }

        if (!$hasWerewolf) {
            $errors[] = __('lobby.validation.need_werewolf');
        }
        if (!$hasVillage) {
            $errors[] = __('lobby.validation.need_village');
        }

        return $errors;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Room::where('code', $code)->exists());

        return $code;
    }

    private function createHost(Room $room, string $nickname): Player
    {
        return Player::create([
            'room_id' => $room->id,
            'nickname' => $nickname,
            'session_token' => (string) Str::uuid(),
            'is_alive' => true,
            'is_host' => true,
            'is_narrator' => true,
        ]);
    }
}
