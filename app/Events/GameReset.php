<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class GameReset implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Room $room
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('room.' . $this->room->id);
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room->id,
        ];
    }
}
