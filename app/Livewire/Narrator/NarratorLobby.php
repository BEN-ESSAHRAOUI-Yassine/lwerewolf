<?php

namespace App\Livewire\Narrator;

use App\Events\PlayerLeft;
use App\Events\SuspiciousAccessAttempt;
use App\Game\Engine\GameEngine;
use App\Game\Services\LobbyService;
use App\Helpers\QrHelper;
use App\Models\Player;
use App\Models\Role;
use App\Models\Room;
use Livewire\Component;

class NarratorLobby extends Component
{
    public Room $room;
    public string $qrSvg = '';
    public array $roleCounts = [];
    public int $playerCount = 0;
    public array $validationErrors = [];
    public bool $canStart = false;

    public function mount(Room $room)
    {
        $player = request()->get('_player');

        if (!$player || !$player->is_narrator || $player->room_id !== $room->id) {
            if ($player) {
                event(new SuspiciousAccessAttempt($player, 'Non-narrator attempted to access narrator lobby'));
            }
            $this->redirect(route('home'));
            return;
        }

        $this->room = $room;

        $ngrokUrl = $room->settings['ngrok_url'] ?? env('APP_URL', 'http://localhost');
        $joinUrl = rtrim($ngrokUrl, '/') . '/join/' . $room->code;
        $this->qrSvg = QrHelper::generate($joinUrl);

        $this->refreshPlayerCount();
        $this->initRoleCounts();
        $this->validateConfig();
    }

    public function refreshPlayerCount()
    {
        $this->playerCount = Player::where('room_id', $this->room->id)
            ->where('is_narrator', false)
            ->count();
    }

    public function initRoleCounts()
    {
        $saved = $this->room->settings['role_counts'] ?? [];
        $roles = Role::orderBy('faction')->orderBy('key')->get();

        foreach ($roles as $role) {
            $this->roleCounts[$role->key] = $saved[$role->key] ?? 0;
        }
    }

    public function incrementRole(string $roleKey)
    {
        if (!isset($this->roleCounts[$roleKey])) return;
        $this->roleCounts[$roleKey]++;
        $this->saveRoleCounts();
    }

    public function decrementRole(string $roleKey)
    {
        if (!isset($this->roleCounts[$roleKey])) return;
        if ($this->roleCounts[$roleKey] <= 0) return;
        $this->roleCounts[$roleKey]--;
        $this->saveRoleCounts();
    }

    private function saveRoleCounts()
    {
        $settings = $this->room->settings ?? [];
        $settings['role_counts'] = $this->roleCounts;
        $this->room->settings = $settings;
        $this->room->save();
        $this->validateConfig();
    }

    public function validateConfig()
    {
        $this->validationErrors = [];
        $this->canStart = false;

        $totalAssigned = array_sum($this->roleCounts);

        if ($this->playerCount < 4) {
            $this->validationErrors[] = __('lobby.validation.min_players');
        }

        if ($totalAssigned !== $this->playerCount) {
            $this->validationErrors[] = __('lobby.validation.role_count_mismatch');
            return;
        }

        $hasWerewolf = false;
        $hasVillage = false;

        foreach ($this->roleCounts as $roleKey => $count) {
            if ($count <= 0) continue;
            $role = Role::where('key', $roleKey)->first();
            if (!$role) continue;

            if ($role->faction === 'werewolves' || $role->key === 'werewolf') {
                $hasWerewolf = true;
            }
            if ($role->faction === 'village') {
                $hasVillage = true;
            }

            if ($roleKey === 'two_sisters' && $count !== 2) {
                $this->validationErrors[] = __('lobby.validation.two_sisters_exact');
            }
            if ($roleKey === 'three_brothers' && $count !== 3) {
                $this->validationErrors[] = __('lobby.validation.three_brothers_exact');
            }
            if (in_array($roleKey, ['white_werewolf', 'pied_piper', 'angel']) && $count > 1) {
                $this->validationErrors[] = __('lobby.validation.solo_max_one');
            }
        }

        if (!$hasWerewolf) {
            $this->validationErrors[] = __('lobby.validation.need_werewolf');
        }
        if (!$hasVillage) {
            $this->validationErrors[] = __('lobby.validation.need_village');
        }

        $this->canStart = empty($this->validationErrors);
    }

    public function removePlayer(int $playerId)
    {
        $player = Player::findOrFail($playerId);
        event(new PlayerLeft($player));
        $player->delete();
        $this->refreshPlayerCount();
        $this->validateConfig();
    }

    public function startGame(GameEngine $engine)
    {
        $player = Player::where('session_token', request()->cookie('session_token'))->first();
        if (!$player || !$player->is_narrator || $player->room_id !== $this->room->id) {
            abort(403);
        }

        $errors = app(LobbyService::class)->validateGameStart($this->room);
        if (!empty($errors)) {
            session()->flash('error', implode(', ', $errors));
            return;
        }

        $engine->startGame($this->room);

        $this->redirect(route('game.narrator', $this->room));
    }

    public function getListeners()
    {
        return [
            "echo-private:room.{$this->room->id},PlayerJoined" => 'refreshPlayerCount',
            "echo-private:room.{$this->room->id},PlayerLeft" => '$refresh',
        ];
    }

    public function render()
    {
        $roles = Role::orderBy('faction')->orderBy('key')->get()->groupBy('faction');

        $this->refreshPlayerCount();
        $this->validateConfig();

        return view('livewire.narrator.narrator-lobby', [
            'roles' => $roles,
            'players' => Player::where('room_id', $this->room->id)
                ->where('is_narrator', false)
                ->orderBy('created_at')
                ->get(),
        ]);
    }
}
