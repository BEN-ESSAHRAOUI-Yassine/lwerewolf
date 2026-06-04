<?php

use Illuminate\Support\Facades\Broadcast;

$guard = ['guards' => ['session-token']];

Broadcast::channel('player.{playerId}', function ($user, $playerId) {
    return $user && $user->id === (int) $playerId;
}, $guard);

Broadcast::channel('narrator.{roomId}', function ($user, $roomId) {
    return $user
        && $user->room_id === (int) $roomId
        && $user->is_narrator === true;
}, $guard);

Broadcast::channel('werewolves.{roomId}', function ($user, $roomId) {
    return $user
        && $user->room_id === (int) $roomId
        && $user->role
        && $user->role->faction === 'werewolves';
}, $guard);

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return $user && $user->room_id === (int) $roomId;
}, $guard);
