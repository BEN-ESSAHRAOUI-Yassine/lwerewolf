<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class LoversRevealed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $roomId,
        public string $publicMessage,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->publicMessage,
        ];
    }
}
