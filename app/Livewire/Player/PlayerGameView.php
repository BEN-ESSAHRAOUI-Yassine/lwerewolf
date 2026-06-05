<?php

namespace App\Livewire\Player;

use App\Events\SuspiciousAccessAttempt;
use App\Models\GameState;
use App\Models\Player;
use App\Models\Room;
use Livewire\Component;

class PlayerGameView extends Component
{
    public Room $room;
    public Player $player;
    public ?GameState $state = null;
    public bool $ready = false;

    public function mount(Room $room)
    {
        $requestPlayer = request()->get('_player');

        if (!$requestPlayer || $requestPlayer->room_id !== $room->id || $requestPlayer->is_narrator) {
            if ($requestPlayer) {
                event(new SuspiciousAccessAttempt($requestPlayer, 'Non-player attempted game view'));
            }
            $this->redirect(route('home'));
            return;
        }

        if (!$room->gameState) {
            $this->redirect(route('lobby.player', $room));
            return;
        }

        $this->room = $room;
        $this->player = $requestPlayer->fresh(['role']);
        $this->state = $room->gameState;

        $readyPlayers = $this->state?->data['players_ready'] ?? [];
        $this->ready = in_array($this->player->id, $readyPlayers);
    }

    public function getListeners()
    {
        $roomId = $this->room->id;
        return [
            "echo-private:room.{$roomId},PhaseChanged" => 'onPhaseChanged',
            "echo-private:room.{$roomId},PlayerEliminated" => 'onPlayerEliminated',
            "echo-private:room.{$roomId},NightResolved" => 'onNightResolved',
            "echo-private:room.{$roomId},LoverDied" => 'onLoverDied',
            "echo-private:room.{$roomId},VillageIdiotRevealed" => 'onVillageIdiot',
            "echo-private:room.{$roomId},GameFinished" => 'onGameFinished',
            "echo-private:room.{$roomId},GameReset" => 'onGameReset',
            "echo-private:player.{$this->player->id},RoleAssigned" => 'onRoleAssigned',
            "echo-private:player.{$this->player->id},SeerResultReady" => 'onSeerResult',
            "echo-private:player.{$this->player->id},FoxResultReady" => 'onFoxResult',
        ];
    }

    public function readyUp()
    {
        if (!$this->state || $this->state->phase !== 'waiting') return;

        $data = $this->state->data ?? [];
        $ready = $data['players_ready'] ?? [];

        if (in_array($this->player->id, $ready)) return;

        $ready[] = $this->player->id;
        $data['players_ready'] = $ready;
        $this->state->data = $data;
        $this->state->save();

        $this->ready = true;

        $aliveCount = Player::where('room_id', $this->room->id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->count();

        if (count($ready) >= $aliveCount) {
            event(new \App\Events\AllPlayersReady($this->room));
        }

        $this->state = $this->state->fresh();
    }

    public function hydrate(): void
    {
        $room = $this->room ?? null;
        if ($room && !$room->gameState) {
            $this->redirect(route('lobby.player', $room));
        }
    }

    public function onPlayerEliminated()
    {
        $this->player = $this->player->fresh(['role']);
        $this->state = $this->room->gameState;
    }

    public function onGameFinished()
    {
        $this->state = $this->room->gameState;
    }

    public function onRoleAssigned()
    {
        $this->player = $this->player->fresh(['role']);
    }

    public function onPhaseChanged(array $payload)
    {
        $gs = $this->room->gameState;
        if (!$gs) {
            $this->state = null;
            return;
        }
        $this->state = $gs->fresh();
        $phase = $this->state->phase;
        $labels = [
            'waiting' => __('ui.phase.waiting'),
            'night' => __('ui.phase.night'),
            'day' => __('ui.phase.day'),
            'voting' => __('ui.phase.voting'),
            'finished' => __('ui.phase.finished'),
        ];
        $classes = [
            'waiting' => 'phase-overlay phase-overlay-waiting',
            'night' => 'phase-overlay phase-overlay-night',
            'day' => 'phase-overlay phase-overlay-day',
            'voting' => 'phase-overlay phase-overlay-voting',
            'finished' => 'phase-overlay phase-overlay-finished',
        ];
        $this->dispatch('transition-phase', label: $labels[$phase] ?? '', class: $classes[$phase] ?? '');
    }

    public function dismissResult(string $type): void
    {
        if (!$this->state) return;
        $data = $this->state->data ?? [];
        unset($data[$type . '_results'][$this->player->id]);
        $this->state->data = $data;
        $this->state->save();
    }

    public function onSeerResult(array $payload)
    {
        $factionKey = $payload['faction'] ?? '';
        $this->dispatch('show-result', [
            'type' => 'seer',
            'nickname' => $payload['target_nickname'] ?? '',
            'faction' => __('ui.factions.' . $factionKey),
        ]);
    }

    public function onFoxResult(array $payload)
    {
        $this->dispatch('show-result', [
            'type' => 'fox',
            'found' => $payload['werewolf_found'] ?? false,
        ]);
    }

    public function onNightResolved(array $payload)
    {
        $this->state = $this->room?->gameState;
        $this->dispatch('show-night-resolved', [
            'eliminated' => $payload['eliminated'] ?? [],
        ]);
    }

    public function onLoverDied(array $payload)
    {
        $this->dispatch('show-result', [
            'type' => 'lover_died',
            'nickname' => $payload['nickname'] ?? '',
            'partner_nickname' => $payload['partner_nickname'] ?? '',
        ]);
    }

    public function onVillageIdiot(array $payload)
    {
        $this->dispatch('show-result', [
            'type' => 'village_idiot',
            'nickname' => $payload['nickname'] ?? '',
        ]);
    }

    public function onGameReset()
    {
        $this->redirect(route('lobby.player', $this->room));
    }

    public function render()
    {
        $this->state = $this->room?->gameState;

        if (!$this->state) {
            $this->redirect(route('lobby.player', $this->room));
            return;
        }

        $players = collect();
        if ($this->state->phase === 'finished') {
            $players = Player::where('room_id', $this->room->id)
                ->where('is_narrator', false)
                ->with('role')
                ->orderBy('created_at')
                ->get();
        }

        return view('livewire.player.player-game-view', [
            'state' => $this->state,
            'players' => $players,
        ])->layout('layouts.app');
    }
}
