<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['key' => 'villager', 'faction' => 'village', 'night_order' => null, 'abilities' => null, 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'seer', 'faction' => 'village', 'night_order' => 9, 'abilities' => [['key' => 'inspect', 'target' => 'single_player', 'result' => 'faction', 'private' => true, 'once_per' => 'night']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'witch', 'faction' => 'village', 'night_order' => 10, 'abilities' => [['key' => 'save_potion', 'target' => 'single_player', 'once_per' => 'game', 'uses' => 1], ['key' => 'poison_potion', 'target' => 'single_player', 'once_per' => 'game', 'uses' => 1]], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'hunter', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'last_shot', 'trigger' => 'on_elimination']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'bodyguard', 'faction' => 'village', 'night_order' => 7, 'abilities' => [['key' => 'protect', 'target' => 'single_player', 'once_per' => 'night']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'little_girl', 'faction' => 'village', 'night_order' => 8, 'abilities' => [['key' => 'spy', 'passive' => true]], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'cupid', 'faction' => 'village', 'night_order' => 0, 'abilities' => [['key' => 'link', 'target' => 'two_players', 'once_per' => 'game', 'night' => 1]], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'elder', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'resilience', 'passive' => true], ['key' => 'fragility', 'trigger' => 'on_vote_elimination']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'scapegoat', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'sacrifice', 'trigger' => 'on_tie'], ['key' => 'last_decree', 'trigger' => 'after_sacrifice']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'village_idiot', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'revealed_innocence', 'trigger' => 'on_vote_elimination']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'two_sisters', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'kinship', 'passive' => true]], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'three_brothers', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'kinship', 'passive' => true]], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'stuttering_judge', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'second_vote', 'once_per' => 'game']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'knight_with_rusty_sword', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'rusty_wound', 'trigger' => 'on_werewolf_kill']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'devoted_servant', 'faction' => 'village', 'night_order' => null, 'abilities' => [['key' => 'sacrifice', 'trigger' => 'on_vote_elimination']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'bear_tamer', 'faction' => 'village', 'night_order' => 13, 'abilities' => [['key' => 'bear_growl', 'passive' => true]], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'fox', 'faction' => 'village', 'night_order' => 12, 'abilities' => [['key' => 'sniff', 'target' => 'three_adjacent_players', 'once_per' => 'night']], 'win_condition' => 'all werewolves eliminated'],
            ['key' => 'werewolf', 'faction' => 'werewolves', 'night_order' => 4, 'abilities' => [['key' => 'group_kill', 'target' => 'single_player', 'once_per' => 'night', 'collective' => true]], 'win_condition' => 'parity with village'],
            ['key' => 'big_bad_wolf', 'faction' => 'werewolves', 'night_order' => 5, 'abilities' => [['key' => 'extra_kill', 'target' => 'single_player', 'once_per' => 'night', 'condition' => 'no_wolf_died']], 'win_condition' => 'parity with village'],
            ['key' => 'accursed_wolf_father', 'faction' => 'werewolves', 'night_order' => 3, 'abilities' => [['key' => 'convert', 'target' => 'single_player', 'once_per' => 'game', 'replaces' => 'kill']], 'win_condition' => 'parity with village'],
            ['key' => 'white_werewolf', 'faction' => 'werewolves', 'night_order' => 6, 'abilities' => [['key' => 'solo_kill', 'target' => 'single_player', 'cadence' => 'every_other_night']], 'win_condition' => 'last player standing'],
            ['key' => 'wolf_hound', 'faction' => 'werewolves', 'night_order' => 2, 'abilities' => [['key' => 'choose_side', 'once_per' => 'game', 'night' => 1]], 'win_condition' => 'depends on chosen faction'],
            ['key' => 'pied_piper', 'faction' => 'pied_piper', 'night_order' => 11, 'abilities' => [['key' => 'enchant', 'target' => 'single_player', 'once_per' => 'night']], 'win_condition' => 'all living players enchanted'],
            ['key' => 'angel', 'faction' => 'angel', 'night_order' => null, 'abilities' => [['key' => 'divine_favor', 'trigger' => 'on_vote_elimination_in_round_1']], 'win_condition' => 'eliminated by vote in round 1'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['key' => $role['key']],
                $role
            );
        }
    }
}
