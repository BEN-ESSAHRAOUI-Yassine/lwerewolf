# specs/action-system.md — Action System Specification

## Responsibilities

- Accept night action submissions from players
- Validate actions (eligibility, target, ability state)
- Store pending actions in DB (no resolution on submit)
- Resolve all actions as a deferred batch at dawn (triggered by narrator)
- Enforce resolution order
- Handle conflicts (protection vs kill, save vs kill)
- Broadcast private results to relevant players
- Update game state after resolution

---

## Core Principle: Deferred Resolution

Actions are **never resolved on submission**.

Submit = store as pending in `night_actions` table.
Resolve = narrator triggers "Resolve Night" → ActionResolver processes all pending actions in batch.

This keeps conflict handling clean and resolution predictable.

---

## Action Submission Flow

1. Narrator wakes a role via dashboard ("Wake Seer")
2. The relevant player(s) receive an active action panel on their screen
3. Player selects their action and target
4. Confirmation step (anti-misclick)
5. Action stored in `night_actions` as unresolved (`resolved_at = null`)
6. `NightActionSubmitted` event fires → narrator live feed updates
7. Player screen shows masked "Action submitted" confirmation
8. Narrator wakes next role

---

## night_actions Table

```
id
game_state_id     FK → game_states.id
player_id         FK → players.id         — who submitted
action_type       VARCHAR                  — kill, inspect, save, poison, protect, enchant, sniff, convert, solo_kill, choose_side
target_id         FK → players.id nullable — who is targeted (null for some actions)
metadata          JSON nullable            — extra data (e.g. witch choice, fox group, wolf hound choice)
resolved_at       TIMESTAMP nullable       — null = pending, set on resolution
created_at
```

---

## Action Types Reference

| action_type | Role | Target | Notes |
|---|---|---|---|
| `kill` | Werewolves | Single player | Collective — last submitted wins |
| `extra_kill` | Big Bad Wolf | Single player | Only if no werewolf has died |
| `convert` | Accursed Wolf-Father | Single player | Once per game — replaces kill |
| `solo_kill` | White Werewolf | Single werewolf | Every other night, optional |
| `protect` | Bodyguard | Single player | Cannot repeat same target |
| `inspect` | Seer | Single player | Result: faction only |
| `save` | Witch | Single player | Once per game — targets werewolf kill victim |
| `poison` | Witch | Single player | Once per game — independent kill |
| `enchant` | Pied Piper | Two players | Stored as array in metadata |
| `sniff` | Fox | Three adjacent players | Stored as array in metadata |
| `choose_side` | Wolf Hound | null | Choice in metadata: 'village' or 'werewolf' |
| `link_lovers` | Cupid | Two players | Night 1 only — stored in couple_bonds |

---

## Validation Rules (on submission)

| Rule | Detail |
|---|---|
| Player must be alive | Dead players cannot submit actions |
| Player must have the ability | Role must have this action_type |
| One-time abilities enforced | Witch save/poison: checked against game_states.data |
| Target must be alive | Dead players cannot be targeted |
| Target must not be self (most roles) | Enforced per role |
| Bodyguard repeat restriction | Cannot protect same player as last night |
| Big Bad Wolf eligibility | Checked against `game_states.data.wolf_father_used` is not applicable — checks if any werewolf has died |
| Wolf Hound one-time choice | Can only submit `choose_side` on night 1 |
| Fox ability active | Cannot submit if `fox_ability_active = false` |
| White Werewolf parity night | Solo kill only available on correct nights |

If validation fails: action rejected, player sees inline error, no DB write.

---

## Resolution Rules (ActionResolver)

### Step 1 — Knight with Rusty Sword Delayed Death
- If `game_states.data.infected_werewolf_id` is set: that werewolf dies before any other resolution
- Clear `infected_werewolf_id` after applying

### Step 2 — Bodyguard Protection
- Mark `protected_player_id` in resolver context (not persisted — used only during this resolution pass)
- Protection applies only to werewolf kills (not witch poison, not Hunter shot)

### Step 3 — Werewolf Kill
- If target is protected by Bodyguard: kill cancelled, no death
- If Accursed Wolf-Father submitted `convert` instead: kill replaced by conversion (see Step 5)
- Otherwise: target marked for death

### Step 4 — Big Bad Wolf Extra Kill
- Only runs if no werewolf has died (checked live — includes Knight delayed death from Step 1)
- Target cannot be same as werewolf kill target
- Target marked for death (not cancellable by Bodyguard)

### Step 5 — Accursed Wolf-Father Conversion
- Mutually exclusive with werewolf kill on same night
- Target's `role_id` updated to Werewolf role
- Target added to werewolf group channel
- `wolf_father_used = true` set in `game_states.data`
- Target is NOT killed — they become a werewolf silently

### Step 6 — White Werewolf Solo Kill
- Only on correct nights (every other night)
- Target must be a werewolf (not White Werewolf themselves)
- Target marked for death

### Step 7 — Witch Save
- If Witch submitted `save` on same target as werewolf kill: cancel that kill
- Witch save does NOT cancel Big Bad Wolf extra kill or White Werewolf solo kill
- `witch_save_used = true` set in `game_states.data`

### Step 8 — Witch Poison
- Independent kill — not cancellable by Bodyguard or Witch save
- Target marked for death
- `witch_poison_used = true` set in `game_states.data`

### Step 9 — Pied Piper Enchant
- Add 2 players to `game_states.data.enchanted_player_ids`
- Private notification broadcast to enchanted players
- After enchanting: run WinConditionChecker for Pied Piper win

### Step 10 — Fox Sniff
- Check if any of the 3 submitted players is a werewolf-faction player
- If yes: result = "werewolf present" (private broadcast to Fox)
- If no: result = "no werewolf" + `fox_ability_active = false`

### Step 11 — Seer Inspect
- Look up target's faction
- Private broadcast to Seer: faction result only (not exact role)
- No effect on target

---

## Death Application (per marked player)

After all resolution steps, apply deaths in order:

For each player marked for death:
1. Fire `LoverDied` if player is in a couple_bond → queue partner for death
2. Fire Hunter ability if player is Hunter → narrator prompted for Hunter shot
3. Check Knight with Rusty Sword if killed by werewolf → set `infected_werewolf_id`
4. Set `players.is_alive = false`
5. Set `night_actions.resolved_at` for all their actions this round
6. Fire `PlayerEliminated`
7. Process queued deaths (partner, Hunter target)

After all deaths applied:
- Run `WinConditionChecker`
- Fire `NightResolved` event
- Transition to Day phase

---

## Player Action Panel (Night)

The action panel is a Livewire component (`NightAction`) shown only when the narrator
has woken the relevant role.

| Element | Detail |
|---|---|
| Instruction text | Role-specific prompt (e.g. "Choisissez une cible" / "Choose a target") |
| Target list | Living players, role-specific filters applied |
| Selection | Tap to select |
| Confirmation | Anti-misclick confirmation before submit |
| Submitted state | Shows masked "Action submitted" — hold to reveal |
| Masked state | Black card face — default |

### Mask / Unmask on Action Panel

- Default: black card face (action hidden)
- Hold to reveal: shows submitted action + target
- Release: returns to black card face
- Applies to: submitted night action AND any result received (e.g. Seer inspect result)

---

## Witch Special Panel

Witch sees two sequential prompts:

1. First: shown the werewolf kill target (private broadcast)
   - Option A: "Use save potion" (if available)
   - Option B: "Do not save"
2. Second: poison potion prompt (if available)
   - Option A: "Use poison potion" — select target
   - Option B: "Do not poison"

Both prompts are separate and sequential. Witch may use both, one, or neither.

---

## Werewolf Group Panel

All werewolves share a common view of each other during the night:
- List of all werewolf-faction players (names visible to each other)
- Shared kill target selector — any werewolf can submit/change the target
- Last submitted target is used at resolution
- Narrator sees all werewolf submissions in live feed

---

## Cupid Night 1 Panel

- Cupid selects exactly 2 players from the full player list
- Cannot select self twice (can select self + one other)
- On submit: `couple_bonds` record created
- Both linked players are then privately notified of each other (separate private broadcasts)

---

## Real-Time Events (Reverb)

| Event | Channel | Triggered by |
|---|---|---|
| `NightActionSubmitted` | `narrator.{room_id}` | Any player submits action |
| `NightResolved` | `room.{room_id}` | ActionResolver completes |
| `PlayerEliminated` | `room.{room_id}` | Death applied |
| `LoverDied` | `room.{room_id}` | Lover death chain triggered |
| `SeerResultReady` | `player.{seer_id}` | Seer inspect resolved |
| `WitchKillRevealed` | `player.{witch_id}` | Werewolf kill target shown to Witch |
| `FoxResultReady` | `player.{fox_id}` | Fox sniff resolved |
| `EnchantedNotification` | `player.{player_id}` | Pied Piper enchanted this player |
| `LoversLinked` | `player.{player_id}` | Cupid linked — shows partner identity |
| `ConvertedToWerewolf` | `player.{player_id}` | Wolf-Father converted this player |
