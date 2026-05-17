<?php

namespace App\Events;

use App\Models\GameState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PhaseChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public GameState $state
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('room.' . $this->state->room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'phase' => $this->state->phase,
            'round' => $this->state->round,
        ];
    }
}
