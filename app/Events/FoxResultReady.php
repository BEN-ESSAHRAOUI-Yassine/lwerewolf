<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class FoxResultReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Player $fox,
        public bool $werewolfFound
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('player.' . $this->fox->id);
    }

    public function broadcastWith(): array
    {
        return [
            'werewolf_found' => $this->werewolfFound,
        ];
    }
}
