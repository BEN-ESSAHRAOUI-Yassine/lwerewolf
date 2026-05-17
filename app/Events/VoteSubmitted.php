<?php

namespace App\Events;

use App\Models\Vote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class VoteSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Vote $vote
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('narrator.' . $this->vote->gameState->room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'voter_id' => $this->vote->voter_id,
            'target_id' => $this->vote->target_id,
        ];
    }
}
