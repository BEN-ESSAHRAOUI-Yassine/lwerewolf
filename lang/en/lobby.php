<?php

return [
    'create_room' => 'Create a Game',
    'join_room' => 'Join a Game',
    'room_code' => 'Room Code',
    'your_nickname' => 'Your Nickname',
    'nickname_placeholder' => 'Enter your nickname',
    'scan_to_join' => 'Scan this QR code to join the game',
    'connected_players' => 'Connected Players',
    'no_players_yet' => 'No players have joined yet',
    'you_joined_as' => 'You joined as',
    'waiting_narrator' => 'Waiting for the narrator to start...',
    'role_config' => 'Role Configuration',
    'assigned' => 'Assigned',
    'players' => 'players',
    'errors' => [
        'game_started' => 'This game has already started.',
        'already_joined' => 'You have already joined this room.',
        'nickname_taken' => 'This nickname is already taken.',
        'room_full' => 'This room is full.',
    ],
    'validation' => [
        'min_players' => 'At least 4 players are required to start.',
        'role_count_mismatch' => 'Role count must match the number of players exactly.',
        'need_werewolf' => 'At least 1 werewolf-faction role is required.',
        'need_village' => 'At least 1 village-faction role is required.',
        'two_sisters_exact' => 'Two Sisters requires exactly 2 players.',
        'three_brothers_exact' => 'Three Brothers requires exactly 3 players.',
        'solo_max_one' => 'Solo faction roles can have at most 1 each.',
    ],
];
