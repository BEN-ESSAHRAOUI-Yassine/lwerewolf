<?php

use App\Models\Player;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('player.{playerId}', function ($request, $playerId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player && $player->id === (int) $playerId;
});

Broadcast::channel('narrator.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player
        && $player->room_id === (int) $roomId
        && $player->is_narrator === true;
});

Broadcast::channel('werewolves.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))
                    ->with('role')->first();
    return $player
        && $player->room_id === (int) $roomId
        && $player->role
        && $player->role->faction === 'werewolves';
});

Broadcast::channel('room.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player && $player->room_id === (int) $roomId;
});
