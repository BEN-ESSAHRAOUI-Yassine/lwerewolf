<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NarratorMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Player $player,
        public string $message,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('player.' . $this->player->id);
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
