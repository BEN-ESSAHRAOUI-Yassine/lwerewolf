<?php

namespace App\Livewire\Player;

use App\Models\NightAction as NightActionModel;
use App\Models\Player;
use App\Models\Room;
use Livewire\Component;

class NightAction extends Component
{
    public Room $room;
    public Player $player;
    public ?string $selectedTargetId = null;
    public bool $submitted = false;
    public bool $confirming = false;
    public ?NightActionModel $submittedAction = null;
    public array $alivePlayers = [];
    public bool $isDecoy = false;

    public function mount(Room $room, Player $player)
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || $requestPlayer->id !== $player->id) {
            abort(403);
        }

        $this->room = $room;
        $this->player = $player;

        if (!$player->is_alive || $player->is_narrator) return;

        $state = $room->gameState;
        if (!$state || $state->phase !== 'night') return;

        $role = $player->role;

        $this->alivePlayers = Player::where('room_id', $room->id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->where('id', '!=', $player->id)
            ->orderBy('nickname')
            ->get()
            ->toArray();

        $existing = NightActionModel::where('game_state_id', $state->id)
            ->where('player_id', $player->id)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            $this->submitted = true;
            $this->submittedAction = $existing;
        }

        $this->isDecoy = !$role || $role->night_order === null;
    }

    public function selectTarget(string $targetId)
    {
        $this->selectedTargetId = $targetId;
        $this->confirming = true;
    }

    public function cancelSelection()
    {
        $this->selectedTargetId = null;
        $this->confirming = false;
    }

    private function getActionTypeForRole(string $roleKey): ?string
    {
        return match ($roleKey) {
            'werewolf' => 'kill',
            'big_bad_wolf' => 'extra_kill',
            'accursed_wolf_father' => 'convert',
            'white_werewolf' => 'solo_kill',
            'bodyguard' => 'protect',
            'seer' => 'inspect',
            'witch' => 'save',
            'pied_piper' => 'enchant',
            'fox' => 'sniff',
            'cupid' => 'link_lovers',
            default => null,
        };
    }

    public function confirmSubmit()
    {
        $requestPlayer = request()->get('_player');
        if (!$requestPlayer || $requestPlayer->id !== $this->player->id) {
            abort(403);
        }

        $state = $this->room->gameState;
        if (!$state || $state->phase !== 'night' || !$this->player->is_alive) return;

        if ($this->isDecoy) {
            $this->submitted = true;
            $this->confirming = false;
            return;
        }

        $role = $this->player->role;
        if (!$role || $role->night_order === null) return;

        $actionType = $this->getActionTypeForRole($role->key);
        if (!$actionType) return;

        $action = app(\App\Game\Services\ActionService::class)->submit($this->player, [
            'action_type' => $actionType,
            'target_id' => $this->selectedTargetId,
        ]);

        if ($action) {
            $this->submitted = true;
            $this->submittedAction = $action;
            $this->confirming = false;
        }
    }

    public function render()
    {
        $role = $this->player->role;

        return view('livewire.player.night-action', [
            'role' => $role,
            'isDecoy' => $this->isDecoy,
        ]);
    }
}
