# specs/role-system.md — Role System Specification

## Core Principles

- Every role is modular and data-driven
- No hardcoded role logic in the Game Engine
- All roles implement `RoleInterface`
- Night order is explicit — lower number acts first
- Faction is a first-class concept, not a string tag
- Win conditions are checked by `WinConditionChecker`, not by roles themselves

---

## Factions

| Faction Key | Win Condition |
|---|---|
| `village` | All werewolves are eliminated |
| `werewolves` | Werewolves reach numeric parity with village-aligned players |
| `lovers` | Both lovers survive to the end (cross-faction only — see rules below) |
| `pied_piper` | All living players are enchanted |
| `white_werewolf` | White Werewolf is the last player standing |
| `angel` | Angel is eliminated by village vote during round 1 |

### Lovers Faction Rules

- If both lovers belong to the same faction and that faction wins while both are alive → faction wins, no Lovers override
- If lovers are cross-faction → werewolf faction wins when werewolves reach their win condition
- When one lover dies (any cause) → partner dies immediately, death chain resolves fully
- If the dying lover has a death ability (Hunter) → ability fires before partner dies

---

## Night Order Reference

| Order | Role |
|---|---|
| 0 | Cupid (night 1 only) |
| 1 | Lovers (notified of each other, night 1 only) |
| 2 | Wolf Hound (chooses faction, night 1 only) |
| 3 | Werewolves |
| 4 | Big Bad Wolf |
| 5 | Accursed Wolf-Father |
| 6 | White Werewolf (every other night) |
| 7 | Bodyguard |
| 8 | Little Girl (passive — may peek during werewolf phase) |
| 9 | Seer |
| 10 | Witch |
| 11 | Pied Piper |
| 12 | Fox |
| 13 | Bear Tamer (passive — narrator announces result at dawn) |
| — | Villager, Elder, Scapegoat, Village Idiot, Two Sisters, Three Brothers, Knight with Rusty Sword, Devoted Servant, Hunter, Stuttering Judge — no night action |

---

## Role Definitions

---

### VILLAGE FACTION

---

#### Villager
```
key:          villager
faction:      village
night_order:  null
abilities:    none
win_condition: all werewolves eliminated
```
No special ability. The backbone of the village.

---

#### Seer
```
key:          seer
faction:      village
night_order:  9
abilities:
  - inspect: target one player per night, learn their faction (village or werewolf)
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Result is private — broadcast only to `player.{seer_id}`
- Result shows faction, not exact role
- Wolf Hound who chose werewolf appears as werewolf to Seer
- White Werewolf appears as werewolf to Seer

---

#### Witch
```
key:          witch
faction:      village
night_order:  10
abilities:
  - save_potion: cancel the werewolf kill this night (once per game)
  - poison_potion: kill one player this night (once per game)
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Witch is shown the werewolf kill target before deciding to save
- Save potion and poison potion are tracked as booleans on the player record (or in game_states.data)
- Poison kill is independent — not cancellable by Bodyguard
- Witch may use both potions in the same night
- Witch may not use save potion on herself if she is the kill target (optional rule — configurable in room settings)

---

#### Hunter
```
key:          hunter
faction:      village
night_order:  null
abilities:
  - last_shot: when eliminated (any cause), immediately kills one player of choice
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Ability fires on elimination, before any death chain continues
- Hunter must select a target — narrator prompts immediately on elimination
- If Hunter is a lover and partner dies, Hunter fires before partner death resolves
- Hunter cannot target an already dead player

---

#### Bodyguard
```
key:          bodyguard
faction:      village
night_order:  7
abilities:
  - protect: choose one player to protect from werewolf kill this night
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Bodyguard cannot protect the same player two nights in a row
- Protection cancels only the werewolf kill — does not block witch poison or Hunter shot
- Bodyguard may protect themselves

---

#### Little Girl
```
key:          little_girl
faction:      village
night_order:  8
abilities:
  - spy: may open eyes briefly during werewolf phase to try to identify werewolves
win_condition: all werewolves eliminated
```
**Implementation notes:**
- This is a social / physical mechanic, not a digital action
- App surface: narrator dashboard shows a reminder that Little Girl may be watching
- If werewolves catch her (narrator judgment) → she is eliminated immediately
- No digital action submitted — purely handled by narrator

---

#### Cupid
```
key:          cupid
faction:      village
night_order:  0
abilities:
  - link: on night 1 only, choose two players to become lovers
win_condition: all werewolves eliminated (unless linked players trigger Lovers win)
```
**Implementation notes:**
- Cupid may link themselves with another player
- After linking, both players are woken and shown each other's identity (night 1)
- Creates a `couple_bonds` record for the game session
- If Cupid does not act on night 1, no lovers exist this game

---

#### Elder
```
key:          elder
faction:      village
night_order:  null
abilities:
  - resilience: survives the first werewolf attack
  - fragility: if eliminated by village vote, all village special abilities are permanently lost
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Track `elder_first_attack_survived` boolean in `game_states.data`
- If Elder is voted out: all Village-faction role abilities are disabled for the rest of the game (Seer sees nothing, Witch potions are gone, etc.)
- Elder losing abilities on vote-out is a configurable optional rule in room settings

---

#### Scapegoat
```
key:          scapegoat
faction:      village
night_order:  null
abilities:
  - sacrifice: if a village vote ends in a tie, Scapegoat is eliminated instead
  - last_decree: after being sacrificed, Scapegoat chooses which players may or may not vote next round
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Tie detection in VotingSystem checks for Scapegoat presence before applying default tie rule
- Last decree is submitted by the Scapegoat player before they are removed from the game

---

#### Village Idiot
```
key:          village_idiot
faction:      village
night_order:  null
abilities:
  - revealed_innocence: when voted out, their role is revealed, they survive but lose all voting rights permanently
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Track `voting_banned` boolean on the player record
- Village Idiot's role card is publicly revealed by the narrator on this event
- This is the only case where a living player's role is revealed publicly

---

#### Two Sisters
```
key:          two_sisters
faction:      village
night_order:  null (wake together on night 1 and every other night if configured)
abilities:
  - kinship: know each other's identity from the start
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Exactly 2 players receive this role
- On game start (or night 1), both are shown each other via their private channels
- No action submitted — identity reveal only

---

#### Three Brothers
```
key:          three_brothers
faction:      village
night_order:  null (wake together on night 1 and every third night if configured)
abilities:
  - kinship: know each other's identity from the start
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Exactly 3 players receive this role
- Same mechanic as Two Sisters — identity reveal on private channels

---

#### Stuttering Judge
```
key:          stuttering_judge
faction:      village
night_order:  null
abilities:
  - second_vote: once per game, secretly signal the narrator to trigger a second vote immediately after the first
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Signal is sent via the app as a private action (narrator sees it on dashboard)
- The signal is invisible to other players
- Narrator announces a second vote without revealing why

---

#### Knight with Rusty Sword
```
key:          knight_with_rusty_sword
faction:      village
night_order:  null
abilities:
  - rusty_wound: the first werewolf to eat them is infected and dies the following night
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Track `infected_werewolf_id` in `game_states.data` when Knight is killed by werewolves
- At the start of the next night resolution, the infected werewolf dies before any actions resolve
- Infection is secret — narrator knows via dashboard, players do not

---

#### Devoted Servant
```
key:          devoted_servant
faction:      village
night_order:  null
abilities:
  - sacrifice: when a player is voted out, before their role is revealed, Devoted Servant may secretly swap identities with them
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Devoted Servant submits their swap decision privately via app immediately after vote result
- If they swap: the voted-out player takes Devoted Servant's role and is eliminated as Devoted Servant; Devoted Servant takes the other player's role and stays alive
- Narrator is informed via dashboard before the public announcement

---

#### Bear Tamer
```
key:          bear_tamer
faction:      village
night_order:  13
abilities:
  - bear_growl: each morning, if at least one werewolf is sitting directly adjacent to Bear Tamer, narrator growls
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Seating adjacency is defined at game start — store seat order in `game_states.data`
- Narrator dashboard shows growl/no-growl indicator each morning
- Narrator announces the growl publicly (no-growl is also announced)
- If Bear Tamer dies, no more growls

---

#### Fox
```
key:          fox
faction:      village
night_order:  12
abilities:
  - sniff: each night, pick any 3 adjacent players — if at least one is a werewolf, Fox keeps the ability; if none are werewolves, Fox permanently loses the ability
win_condition: all werewolves eliminated
```
**Implementation notes:**
- Fox selects a group of 3 adjacent players
- Result is private — broadcast only to `player.{fox_id}`
- Track `fox_ability_active` boolean in `game_states.data`
- Wolf Hound who chose werewolf counts as a werewolf for Fox detection
- White Werewolf counts as a werewolf for Fox detection

---

#### Pied Piper
```
key:          pied_piper
faction:      pied_piper
night_order:  11
abilities:
  - enchant: each night, enchant 2 living non-enchanted players
win_condition: all living players are enchanted
```
**Implementation notes:**
- Track `enchanted_player_ids` array in `game_states.data`
- Enchanted players do not know they are enchanted (their private channel receives a notification)
- Pied Piper is immune to their own enchantment check
- If Pied Piper is eliminated, enchanted players revert to their original faction win conditions (optional rule — configurable)
- Pied Piper appears as village to the Seer

---

### WEREWOLF FACTION

---

#### Werewolf
```
key:          werewolf
faction:      werewolves
night_order:  3
abilities:
  - kill: collectively choose one player to eliminate each night
win_condition: werewolves reach numeric parity with village-aligned players
```
**Implementation notes:**
- All werewolves share a private group channel `werewolves.{room_id}` to coordinate
- Kill target is submitted by consensus — last submitted target is used, or narrator resolves tie
- Werewolves know each other's identities from the start

---

#### Big Bad Wolf
```
key:          big_bad_wolf
faction:      werewolves
night_order:  4
abilities:
  - extra_kill: after the werewolf kill, eliminate one additional player — but only while no werewolf has died yet
win_condition: werewolves reach numeric parity with village-aligned players
```
**Implementation notes:**
- Track whether any werewolf has died in `game_states.data`
- If a werewolf has died, Big Bad Wolf loses the extra kill permanently
- Big Bad Wolf may not target the same player as the werewolf kill

---

#### Accursed Wolf-Father
```
key:          accursed_wolf_father
faction:      werewolves
night_order:  5
abilities:
  - convert: once per game, instead of killing the chosen victim, secretly convert them into a werewolf
win_condition: werewolves reach numeric parity with village-aligned players
```
**Implementation notes:**
- Track `wolf_father_used` boolean in `game_states.data`
- Converted player's role is updated to Werewolf in DB
- Converted player is added to the werewolf group channel
- The village does not know a conversion happened — no public announcement
- Converted player's original role abilities are lost

---

#### White Werewolf
```
key:          white_werewolf
faction:      white_werewolf
night_order:  6
abilities:
  - werewolf_kill: participates in normal werewolf kill each night
  - solo_kill: every other night, may eliminate one werewolf (optional, not mandatory)
win_condition: last player standing alone
```
**Implementation notes:**
- White Werewolf appears as werewolf to Seer and Fox
- White Werewolf is known to other werewolves as one of them
- Solo kill is optional — White Werewolf may pass
- Track `white_werewolf_solo_night` counter in `game_states.data`
- If White Werewolf wins, all other players lose regardless of faction

---

#### Wolf Hound
```
key:          wolf_hound
faction:      werewolves (or village — chosen on night 1)
night_order:  2
abilities:
  - choose_side: on night 1, secretly choose to be a villager or a werewolf
win_condition: depends on chosen faction
```
**Implementation notes:**
- Choice is submitted via private channel on night 1
- If villager chosen: Wolf Hound is treated as a Villager for all purposes, not added to werewolf channel
- If werewolf chosen: Wolf Hound joins the werewolf group channel, appears as werewolf to Seer
- Choice is permanent and secret
- Track `wolf_hound_choice` in `game_states.data`

---

### NEUTRAL / SOLO FACTION

---

#### Angel
```
key:          angel
faction:      angel
night_order:  null
abilities:
  - early_redemption: if eliminated by village vote during round 1, Angel wins immediately
  - fallback: if not eliminated by vote in round 1, Angel joins the village faction for the rest of the game
win_condition: eliminated by village vote in round 1
```
**Implementation notes:**
- Track current round in `game_states.round`
- WinConditionChecker checks Angel win condition only after a vote elimination in round 1
- If Angel survives round 1 vote: faction is updated to `village` in DB
- Angel win is an immediate solo win — game ends

---

## Role Structure Reference (PHP)

```php
// Example: Seer
class Seer extends BaseRole implements RoleInterface
{
    public function getKey(): string { return 'seer'; }

    public function getName(string $locale): string
    {
        return __('roles.seer.name', [], $locale);
    }

    public function getFaction(): string { return 'village'; }

    public function getNightOrder(): ?int { return 9; }

    public function hasNightAction(): bool { return true; }

    public function getAbilities(): array
    {
        return [
            [
                'key'         => 'inspect',
                'target'      => 'single_player',
                'result'      => 'faction',
                'private'     => true,
                'once_per'    => 'night',
            ]
        ];
    }

    public function getWinCondition(): string
    {
        return 'all werewolves eliminated';
    }
}
```

---

## Role Count Constraints (Narrator Config)

| Role | Min players | Max allowed in one game | Notes |
|---|---|---|---|
| Werewolf | 4 | ~1 per 3 players | Core role |
| Villager | 4 | unlimited | Filler |
| Two Sisters | 6 | 1 set (2 players) | Exactly 2 |
| Three Brothers | 7 | 1 set (3 players) | Exactly 3 |
| Cupid | 5 | 1 | Creates couple_bond |
| White Werewolf | 6 | 1 | Solo faction |
| Pied Piper | 7 | 1 | Solo faction |
| Angel | 5 | 1 | Solo faction |
| All others | varies | 1 | One per game |

---

## Win Condition Priority (WinConditionChecker)

Checked after every elimination and every vote resolution, in this order:

1. Angel (round 1 vote only)
2. White Werewolf (last standing)
3. Pied Piper (all living enchanted)
4. Werewolves (parity reached)
5. Village (all werewolves eliminated)
6. Lovers (cross-faction, both survived — checked last)

If no condition is met, game continues.
