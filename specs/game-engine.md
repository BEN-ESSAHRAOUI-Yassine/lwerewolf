# specs/game-engine.md — Game Engine Specification

## Responsibilities

- Maintain authoritative game state
- Process phase transitions via PhaseManager
- Orchestrate deferred night action resolution via ActionResolver
- Check win conditions after every elimination and vote
- Fire domain events on every state change
- Remain fully independent from UI layer

---

## Core Classes

### GameEngine

`app/Game/Engine/GameEngine.php`

The top-level orchestrator. Called by Services, never by Controllers directly.

Responsibilities:
- Start a game (assign roles, create game_state, fire GameStarted)
- Delegate phase transitions to PhaseManager
- Delegate action resolution to ActionResolver
- Delegate win checking to WinConditionChecker

```php
class GameEngine
{
    public function startGame(Room $room): GameState;
    public function advancePhase(GameState $state, string $toPhase): void;
    public function resolveNight(GameState $state): void;
    public function checkWinConditions(GameState $state): ?string; // returns winning faction key or null
}
```

---

### PhaseManager

`app/Game/Engine/PhaseManager.php`

**The only class allowed to write to `game_states.phase`.**

Never update phase directly from a Controller, Service, Livewire component, or any other class.

```php
class PhaseManager
{
    public function transition(GameState $state, string $toPhase): void;
    public function getCurrentPhase(GameState $state): PhaseInterface;
    public function isValidTransition(string $fromPhase, string $toPhase): bool;
}
```

Valid transitions:

| From | To |
|---|---|
| `waiting` | `night` |
| `night` | `day` |
| `day` | `voting` |
| `voting` | `night` (next round) |
| `voting` | `finished` (win condition met) |
| `night` | `finished` (win condition met after night resolution) |

Any other transition is rejected and logs an error.

---

### ActionResolver

`app/Game/Engine/ActionResolver.php`

Resolves all pending night actions as a deferred batch when the narrator triggers dawn.

```php
class ActionResolver
{
    public function resolve(GameState $state): void;
    private function collectPendingActions(GameState $state): Collection;
    private function sortByPriority(Collection $actions): Collection;
    private function applyAction(ActionInterface $action, GameState $state): void;
    private function applyDeathChain(Player $player, GameState $state): void;
}
```

#### Resolution Order

1. Knight with Rusty Sword delayed death (if infected werewolf exists from previous night)
2. Bodyguard protection (mark protected player)
3. Werewolf kill (cancelled if target is protected by Bodyguard)
4. Big Bad Wolf extra kill (cancelled if any werewolf has died)
5. Accursed Wolf-Father conversion (instead of kill — mutually exclusive with werewolf kill on same target)
6. White Werewolf solo kill (every other night, optional)
7. Witch save (cancels werewolf kill on same target — does not cancel other kills)
8. Witch poison (independent kill — not cancellable by Bodyguard)
9. Pied Piper enchant (marks 2 players as enchanted in game_states.data)
10. Fox sniff (result only — no effect on targets)
11. Seer inspect (result only — private broadcast to seer)

#### Death Chain Rules

When a player is marked for death during resolution:
1. Check if player is a Lover → if yes, queue partner death
2. Check if player is Hunter → if yes, queue Hunter shot (narrator prompted)
3. Check if player is Knight with Rusty Sword being killed by werewolf → mark infecting werewolf
4. Apply death (set `is_alive = false`)
5. Fire `PlayerEliminated` event
6. Process queued deaths in order (partner, Hunter shot target)
7. After all deaths resolved, run `WinConditionChecker`

---

### WinConditionChecker

`app/Game/Engine/WinConditionChecker.php`

Checks all factions after every `PlayerEliminated` event and after every vote resolution.

```php
class WinConditionChecker
{
    public function check(GameState $state): ?FactionInterface; // returns winning faction or null
    private function getAlivePlayers(GameState $state): Collection;
    private function checkAngel(GameState $state): bool;
    private function checkWhiteWerewolf(GameState $state): bool;
    private function checkPiedPiper(GameState $state): bool;
    private function checkWerewolves(GameState $state): bool;
    private function checkVillage(GameState $state): bool;
    private function checkLovers(GameState $state): bool;
}
```

#### Check Priority Order

1. **Angel** — only checked after vote elimination in round 1
2. **White Werewolf** — checked if White Werewolf is the only survivor
3. **Pied Piper** — checked if all living players are enchanted
4. **Werewolves** — checked if werewolf count >= village-aligned count
5. **Village** — checked if all werewolf-faction players are dead
6. **Lovers** — checked last (cross-faction lovers survival — see Lovers rules)

If multiple conditions are true simultaneously, the highest priority wins.

#### Werewolf Parity Rule

Werewolves win when:
`alive_werewolf_count >= alive_village_aligned_count`

Village-aligned count includes: all Village faction players + Wolf Hound if chose village +
Angel if past round 1 (joined village).

Does not include: White Werewolf, Pied Piper (solo factions).

---

## Game State

### game_states table

```
id
room_id           FK → rooms.id (unique — one active state per room)
phase             ENUM(waiting, night, day, voting, finished)
round             INT default 1
data              JSON
created_at
updated_at
```

### game_states.data JSON Structure

```json
{
  "enchanted_player_ids": [],
  "wolf_father_used": false,
  "elder_first_attack_survived": false,
  "elder_abilities_disabled": false,
  "fox_ability_active": true,
  "bear_tamer_alive": true,
  "seat_order": [],
  "infected_werewolf_id": null,
  "wolf_hound_choice": null,
  "white_werewolf_solo_night": 0,
  "stuttering_judge_used": false,
  "knight_killed_by_werewolf": false,
  "second_vote_triggered": false,
  "pied_piper_eliminated": false
}
```

---

## Phase Definitions

### Waiting
- Players joining lobby
- Narrator configuring roles
- No game_state exists yet (created on game start)

### Night
- All living role-holding players with night actions may submit their action
- Narrator wakes each role in order via dashboard
- Actions stored as pending in `night_actions` table
- Phase ends when narrator triggers "Resolve Night"
- ActionResolver runs, deaths applied, WinConditionChecker runs
- If no win: transition to Day

### Day
- Narrator announces results of the night (who died, Bear Tamer growl, etc.)
- Discussion happens among players — no app interaction required
- Narrator decides when discussion ends and taps "Start Voting"
- Transition to Voting

### Voting
- All alive players (except Village Idiot if banned) submit one vote
- Votes are secret until narrator closes voting
- If tie: check for Scapegoat, otherwise configurable tie rule (re-vote or no elimination)
- If Stuttering Judge signals: second vote triggered after first
- Elimination announced by narrator
- Death chain runs
- WinConditionChecker runs
- If no win: transition to Night (round + 1)

### Finished
- WinConditionChecker fired GameFinished event
- Winning faction displayed to all screens
- Roles of all players revealed publicly
- Narrator dashboard shows full game summary

---

## Round Counter

- Incremented each time the game transitions from Voting back to Night
- Round 1 = first night + first day + first vote
- Used by: Angel win check, White Werewolf solo kill cadence, Bear Tamer logic

---

## Events Fired by Engine

| Event | When |
|---|---|
| `GameStarted` | After role assignment, before first night |
| `PhaseChanged` | Every phase transition |
| `NightActionSubmitted` | Player submits night action |
| `NightResolved` | After ActionResolver completes |
| `LoverDied` | When a lover is marked for death, before partner death |
| `PlayerEliminated` | After each player death (including chain deaths) |
| `VoteSubmitted` | Player submits vote |
| `GameFinished` | WinConditionChecker finds a winner |

---

## Engine Independence Rule

The Game Engine has zero knowledge of:
- HTTP requests
- Livewire components
- Blade views
- WebSocket channels

It only knows: Models, Events, and its own internal classes.
UI updates happen because Events are broadcast — not because the Engine pushes to them.
