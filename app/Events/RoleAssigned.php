<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoleAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Player $player
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('player.' . $this->player->id);
    }

    public function broadcastWith(): array
    {
        $role = $this->player->role;

        return [
            'role_key' => $role->key,
            'faction' => $role->faction,
            'night_order' => $role->night_order,
            'abilities' => $role->abilities,
        ];
    }
}
