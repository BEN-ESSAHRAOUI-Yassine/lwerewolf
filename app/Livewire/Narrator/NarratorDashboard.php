<?php

namespace App\Livewire\Narrator;

use App\Events\GameReset;
use App\Events\LoversRevealed;
use App\Events\NarratorMessageSent;
use App\Events\SuspiciousAccessAttempt;
use App\Game\Engine\GameEngine;
use App\Models\CoupleBond;
use App\Models\GameState;
use App\Models\NightAction;
use App\Models\Player;
use App\Models\Room;
use App\Models\Vote;
use Livewire\Component;

// DO NOT write game_states.phase directly — use PhaseManager::transition()
class NarratorDashboard extends Component
{
    public Room $room;
    public GameState $state;
    public Player $player;
    public array $gameLog = [];
    public array $nightActionFeed = [];
    public array $pendingRoles = [];
    public ?int $messageTargetId = null;
    public string $messageText = '';
    public string $messageRecipient = '';

    public function mount(Room $room)
    {
        $requestPlayer = request()->get('_player');

        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $room->id) {
            if ($requestPlayer) {
                event(new SuspiciousAccessAttempt($requestPlayer, 'Non-narrator attempted narrator dashboard'));
            }
            $this->redirect(route('home'));
            return;
        }

        if ($room->status === 'waiting') {
            $this->redirect(route('lobby.narrator', $room));
            return;
        }

        $this->room = $room;
        $this->player = $requestPlayer;
        $this->state = $room->gameState;
        $this->initGameLog();
        $this->refreshNightFeed();
    }

    public function advancePhase(string $toPhase)
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $this->room->id) {
            event(new SuspiciousAccessAttempt($requestPlayer ?? $this->player, 'Non-narrator attempted phase transition'));
            $this->redirect(route('home'));
            return;
        }

        try {
            $engine = app(GameEngine::class);

            if ($this->state->phase === 'night' && $toPhase === 'day') {
                $engine->resolveNight($this->state);
                $this->addLogEntry('night_resolved', []);
            } elseif ($this->state->phase === 'voting' && $toPhase === 'night') {
                $engine->resolveVote($this->state);
                $this->addLogEntry('voting_resolved', []);
            } else {
                $engine->advancePhase($this->state, $toPhase);
            }

            $this->state = $this->state->fresh();
            $this->addLogEntry('phase_changed', ['from' => $this->state->phase, 'to' => $toPhase]);
            $this->refreshNightFeed();
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function revealLovers()
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $this->room->id) {
            event(new SuspiciousAccessAttempt($requestPlayer ?? $this->player, 'Non-narrator attempted reveal lovers'));
            $this->redirect(route('home'));
            return;
        }

        if ($this->state->phase !== 'night') return;

        $bondsExist = CoupleBond::where('game_state_id', $this->state->id)->exists();
        if ($bondsExist) return;

        $cupidAction = NightAction::where('game_state_id', $this->state->id)
            ->where('action_type', 'link_lovers')
            ->whereNull('resolved_at')
            ->with('target')
            ->first();

        if (!$cupidAction || !$cupidAction->target) return;

        $metadata = $cupidAction->metadata ?? [];
        $partnerId = $metadata['partner_id'] ?? null;
        if (!$partnerId) return;

        $partner = Player::find($partnerId);
        if (!$partner) return;

        CoupleBond::create([
            'game_state_id' => $this->state->id,
            'player_id' => $cupidAction->target->id,
            'partner_id' => $partnerId,
        ]);
        CoupleBond::create([
            'game_state_id' => $this->state->id,
            'player_id' => $partnerId,
            'partner_id' => $cupidAction->target->id,
        ]);

        $data = $this->state->data;
        $data['lover_info'][$cupidAction->target->id] = [
            'partner_nickname' => $partner->nickname,
            'partner_role_key' => $partner->role?->key ?? 'villager',
        ];
        $data['lover_info'][$partnerId] = [
            'partner_nickname' => $cupidAction->target->nickname,
            'partner_role_key' => $cupidAction->target->role?->key ?? 'villager',
        ];
        $this->state->data = $data;
        $this->state->save();

        $this->state = $this->state->fresh();
        $this->addLogEntry('lovers_revealed', [
            'lover_a' => $cupidAction->target->nickname,
            'lover_b' => $partner->nickname,
        ]);
        $this->refreshNightFeed();

        event(new LoversRevealed(
            $this->room->id,
            __('ui.narrator.lovers_revealed_public'),
        ));
    }

    public function openMessageDialog(int $playerId)
    {
        $this->messageTargetId = $playerId;
        $this->messageText = '';
        $player = Player::find($playerId);
        $this->messageRecipient = $player?->nickname ?? '';
    }

    public function cancelMessage()
    {
        $this->messageTargetId = null;
        $this->messageText = '';
        $this->messageRecipient = '';
    }

    public function sendMessage()
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $this->room->id) {
            abort(403);
        }

        if (!$this->messageTargetId || !trim($this->messageText)) return;

        $player = Player::find($this->messageTargetId);
        if (!$player || $player->room_id !== $this->room->id) return;

        event(new NarratorMessageSent($player, $this->messageText));
        $this->addLogEntry('narrator_message', [
            'target_nickname' => $player->nickname,
            'message' => $this->messageText,
        ]);

        $this->messageTargetId = null;
        $this->messageText = '';
        $this->messageRecipient = '';
    }

    public function kickPlayer(int $playerId)
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $this->room->id) {
            abort(403);
        }

        $player = Player::findOrFail($playerId);
        if ($player->room_id !== $this->room->id || !$player->is_alive) return;

        $player->is_alive = false;
        $player->save();

        $this->addLogEntry('player_kicked', ['nickname' => $player->nickname]);
        $this->state = $this->state->fresh();

        $winner = app(\App\Game\Engine\WinConditionChecker::class)->check($this->state);
        if ($winner) {
            app(\App\Game\Engine\GameEngine::class)->endGame($this->state, $winner);
        }

        $this->state = $this->state->fresh();
    }

    public function newGame()
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || !$requestPlayer->is_narrator || $requestPlayer->room_id !== $this->room->id) {
            event(new SuspiciousAccessAttempt($requestPlayer ?? $this->player, 'Non-narrator attempted new game'));
            $this->redirect(route('home'));
            return;
        }

        $room = $this->room;

        $room->status = 'waiting';
        $room->save();

        Player::where('room_id', $room->id)->update([
            'role_id' => null,
            'is_alive' => true,
            'voting_banned' => false,
        ]);

        if ($room->gameState) {
            $stateId = $room->gameState->id;

            NightAction::where('game_state_id', $stateId)->delete();
            Vote::where('game_state_id', $stateId)->delete();
            CoupleBond::where('game_state_id', $stateId)->delete();
            GameState::where('room_id', $room->id)->delete();
        }

        event(new GameReset($room));

        $this->redirect(route('lobby.narrator', $room));
    }

    private function initGameLog()
    {
        $this->gameLog = [];
        $this->addLogEntry('game_started', ['round' => $this->state->round]);
    }

    private function addLogEntry(string $type, array $data)
    {
        $this->gameLog[] = array_merge([
            'type' => $type,
            'round' => $this->state->round ?? 1,
            'phase' => $this->state->phase ?? 'waiting',
            'timestamp' => now()->toIso8601String(),
        ], $data);

        if (count($this->gameLog) > 200) {
            array_shift($this->gameLog);
        }
    }

    private function refreshNightFeed()
    {
        if ($this->state->phase !== 'night') {
            $this->nightActionFeed = [];
            $this->pendingRoles = [];
            return;
        }

        $actions = NightAction::where('game_state_id', $this->state->id)
            ->whereNull('resolved_at')
            ->with(['player.role', 'target'])
            ->orderBy('created_at')
            ->get();

        $this->nightActionFeed = $actions->map(function ($a) {
            return [
                'id' => $a->id,
                'role_key' => $a->player->role?->key,
                'action_type' => $a->action_type,
                'target_nickname' => $a->target?->nickname,
                'player_nickname' => $a->player->nickname,
                'timestamp' => $a->created_at->toIso8601String(),
            ];
        })->toArray();

        $rolesWithActions = [
            'cupid', 'wolf_hound', 'werewolf', 'big_bad_wolf', 'accursed_wolf_father',
            'white_werewolf', 'bodyguard', 'seer', 'witch', 'pied_piper', 'fox',
        ];

        $submittedPlayerIds = $actions->pluck('player_id')->toArray();
        $allAlive = Player::where('room_id', $this->room->id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->with('role')
            ->get();

        $pending = [];
        foreach ($allAlive as $p) {
            if ($p->role && in_array($p->role->key, $rolesWithActions)) {
                if (!in_array($p->id, $submittedPlayerIds)) {
                    $pending[] = $p->role->key;
                }
            }
        }
        $this->pendingRoles = array_unique($pending);
    }

    public function getListeners()
    {
        return [
            "echo-private:room.{$this->room->id},PhaseChanged" => 'onPhaseChanged',
            "echo-private:room.{$this->room->id},PlayerEliminated" => 'onPlayerEliminated',
            "echo-private:narrator.{$this->room->id},NightActionSubmitted" => 'onNightActionSubmitted',
            "echo-private:narrator.{$this->room->id},VoteSubmitted" => 'onVoteSubmitted',
            "echo-private:narrator.{$this->room->id},SuspiciousAccessAttempt" => 'onSuspiciousAccess',
            "echo-private:room.{$this->room->id},GameFinished" => 'onGameFinished',
            "echo-private:room.{$this->room->id},AllPlayersReady" => 'onAllPlayersReady',
        ];
    }

    public function onPhaseChanged()
    {
        $this->state = $this->state->fresh();
        $this->refreshNightFeed();
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

    public function onPlayerEliminated($payload)
    {
        $this->addLogEntry('player_eliminated', [
            'nickname' => $payload['nickname'] ?? 'unknown',
        ]);
    }

    public function onNightActionSubmitted()
    {
        $this->refreshNightFeed();
    }

    public function onVoteSubmitted()
    {
        $this->addLogEntry('vote_submitted', []);
    }

    public function onSuspiciousAccess($payload)
    {
        $this->addLogEntry('suspicious_access', [
            'player_nickname' => $payload['player']['nickname'] ?? 'unknown',
            'details' => $payload['details'] ?? '',
        ]);
    }

    public function onGameFinished($payload)
    {
        $this->state = $this->state->fresh();
        $this->addLogEntry('game_finished', [
            'winning_faction' => $payload['winning_faction'] ?? 'unknown',
        ]);
    }

    public function onAllPlayersReady()
    {
        $this->addLogEntry('all_players_ready', []);
        $this->advancePhase('night');
    }

    public function render()
    {
        $players = Player::where('room_id', $this->room->id)
            ->where('is_narrator', false)
            ->with('role')
            ->orderBy('created_at')
            ->get();

        $phase = $this->state->phase;
        $availableTransitions = $this->getTransitions($phase);

        $voteTally = [];
        $voteCount = 0;
        if ($phase === 'voting') {
            $votes = Vote::where('game_state_id', $this->state->id)->get();
            $voteCount = $votes->count();
            foreach ($votes as $v) {
                $voteTally[$v->target_id] = ($voteTally[$v->target_id] ?? 0) + 1;
            }
            arsort($voteTally);
        }

        $totalAlive = $players->where('is_alive', true)->count();

        $coupleBonds = CoupleBond::where('game_state_id', $this->state->id)->with(['player', 'partner'])->get();
        $loverMap = [];
        $relations = [];
        $seen = [];
        foreach ($coupleBonds as $bond) {
            $loverMap[$bond->player_id] = $bond->partner_id;
            $loverMap[$bond->partner_id] = $bond->player_id;
            $key = min($bond->player_id, $bond->partner_id) . '-' . max($bond->player_id, $bond->partner_id);
            if (in_array($key, $seen)) continue;
            $seen[] = $key;
            $relations[] = [
                'player_a' => $bond->player,
                'player_b' => $bond->partner,
                'type' => 'lovers',
            ];
        }

        $enchantedIds = $this->state->data['enchanted_player_ids'] ?? [];

        $loversRevealed = CoupleBond::where('game_state_id', $this->state->id)->exists();
        $canRevealLovers = $this->state->phase === 'night'
            && !$loversRevealed
            && NightAction::where('game_state_id', $this->state->id)
                ->where('action_type', 'link_lovers')
                ->whereNull('resolved_at')
                ->exists();

        $nightOrder = [
            'cupid', 'wolf_hound', 'werewolf', 'big_bad_wolf',
            'accursed_wolf_father', 'white_werewolf', 'bodyguard',
            'little_girl', 'seer', 'witch', 'pied_piper', 'fox', 'bear_tamer',
        ];

        $actionHistory = $this->state->data['action_history'] ?? [];

        return view('livewire.narrator.narrator-dashboard', [
            'players' => $players,
            'availableTransitions' => $availableTransitions,
            'voteTally' => $voteTally,
            'voteCount' => $voteCount,
            'totalAlive' => $totalAlive,
            'loverMap' => $loverMap,
            'relations' => $relations,
            'enchantedIds' => $enchantedIds,
            'canRevealLovers' => $canRevealLovers,
            'nightOrder' => $nightOrder,
            'state' => $this->state,
            'actionHistory' => $actionHistory,
        ])->layout('layouts.app');
    }

    private function getTransitions(string $phase): array
    {
        return match ($phase) {
            'night' => ['day', 'finished'],
            'day' => ['voting'],
            'voting' => ['night', 'finished'],
            'finished' => [],
            default => [],
        };
    }
}
