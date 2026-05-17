<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class SuspiciousAccessAttempt implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Player $player,
        public string $details
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('narrator.' . $this->player->room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'player' => [
                'id' => $this->player->id,
                'nickname' => $this->player->nickname,
            ],
            'details' => $this->details,
        ];
    }
}
