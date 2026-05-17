<?php

namespace App\Events;

use App\Models\NightAction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NightActionSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public NightAction $action
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('narrator.' . $this->action->gameState->room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'action_id' => $this->action->id,
            'player_id' => $this->action->player_id,
            'action_type' => $this->action->action_type,
            'target_id' => $this->action->target_id,
        ];
    }
}
