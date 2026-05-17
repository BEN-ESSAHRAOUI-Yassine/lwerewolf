<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class SeerResultReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Player $seer,
        public string $targetNickname,
        public string $factionKey
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('player.' . $this->seer->id);
    }

    public function broadcastWith(): array
    {
        return [
            'target_nickname' => $this->targetNickname,
            'faction' => $this->factionKey,
        ];
    }
}
