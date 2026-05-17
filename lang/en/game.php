<?php

return [
    'phase' => [
        'waiting' => 'Waiting',
        'night' => 'Night',
        'day' => 'Day',
        'voting' => 'Voting',
        'finished' => 'Game Over',
    ],
    'round' => 'Round :number',
    'start' => 'The game begins...',
    'over' => 'Game Over',
    'winner' => 'Winner',
    'players_alive' => 'Players Alive',
    'eliminated' => ':name has been eliminated.',
    'lover_died' => ':name was a Lover. Their partner follows in death.',
    'night_resolved' => 'The night has been resolved.',
    'voting_resolved' => 'The vote has been resolved.',
    'vote_result' => [
        'eliminated' => ':name has been eliminated by the village.',
        'tied' => 'The vote is tied. No elimination this round.',
        'no_consensus' => 'No consensus reached.',
        'village_idiot_spared' => ':nickname is the Village Idiot and survives!',
        'scapegoat_substituted' => 'The Scapegoat is eliminated by the tie rule.',
    ],
    'hunter_kill' => [
        'prompt' => 'Choose your final target as the Hunter.',
        'confirm' => 'Confirm Hunter kill?',
    ],
    'win' => [
        'village' => 'The Village wins!',
        'werewolves' => 'The Werewolves win!',
        'white_werewolf' => 'The White Werewolf wins!',
        'pied_piper' => 'The Pied Piper wins!',
        'angel' => 'The Angel wins!',
        'lovers' => 'The Lovers win!',
        'no_one' => 'No one wins...',
    ],
    'night_summary' => 'Night :round summary: :count action(s) resolved.',
];
