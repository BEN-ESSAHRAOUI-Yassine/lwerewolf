<?php

return [
    'phase' => [
        'waiting' => 'En attente',
        'night' => 'Nuit',
        'day' => 'Jour',
        'voting' => 'Vote',
        'finished' => 'Partie terminée',
    ],
    'round' => 'Tour :number',
    'start' => 'La partie commence...',
    'over' => 'Partie terminée',
    'winner' => 'Gagnant',
    'players_alive' => 'Joueurs vivants',
    'eliminated' => ':name a été éliminé(e).',
    'lover_died' => ':name était un Amoureux. Son/sa partenaire le/la suit dans la mort.',
    'night_resolved' => 'La nuit a été résolue.',
    'voting_resolved' => 'Le vote a été résolu.',
    'vote_result' => [
        'eliminated' => ':name a été éliminé(e) par le village.',
        'tied' => 'Le vote est à égalité. Pas d\'élimination ce tour.',
        'no_consensus' => 'Aucun consensus atteint.',
        'village_idiot_spared' => ':nickname est l\'Idiot du village et survit !',
        'scapegoat_substituted' => 'Le Bouc Émissaire est éliminé par la règle d\'égalité.',
    ],
    'hunter_kill' => [
        'prompt' => 'Choisissez votre cible finale en tant que Chasseur.',
        'confirm' => 'Confirmer la mise à mort du Chasseur ?',
    ],
    'win' => [
        'village' => 'Le Village gagne !',
        'werewolves' => 'Les Loups-Garous gagnent !',
        'white_werewolf' => 'Le Loup-Garou Blanc gagne !',
        'pied_piper' => 'Le Joueur de Flûte gagne !',
        'angel' => 'L\'Ange gagne !',
        'lovers' => 'Les Amoureux gagnent !',
        'no_one' => 'Personne ne gagne...',
    ],
    'night_summary' => 'Nuit :round résumée : :count action(s) résolue(s).',
];
