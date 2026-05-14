# agents.md — Loup-Garou Companion Platform

> This file is the primary instruction document for any AI agent or developer working
> on this codebase. Read it fully before writing any code. Every rule here exists for
> a reason. Do not skip sections.

---

## What This Project Is

A real-life social deduction companion app inspired by Les Loups-Garous de Thiercelieux.
Players are **physically together** in the same room. The app manages hidden information,
roles, narration, game state, voting, and night actions — while keeping human conversation
at the center of the experience.

---

## What This Project Is NOT

Do not build any of the following. If asked to add them, refuse and explain they are
explicitly out of scope:

- An online multiplayer game (no matchmaking, no strangers playing remotely)
- A Town of Salem clone (no automated AI narration in MVP)
- A screen-heavy experience (players should look at each other, not their phones)
- A social network or profile system
- A ranked or competitive system
- A feature-overloaded RPG with progression, cosmetics, or monetization

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel |
| Templating | Blade |
| Reactive UI | Livewire |
| CSS | TailwindCSS |
| Real-time | Laravel Reverb (WebSockets) |
| Tunnel | Ngrok |
| Languages | FR / EN via Laravel `lang/` files |
| Database | SQLite (MVP — local only) |

**Never** introduce new dependencies without explicit approval. Do not add:
- Redis (not needed for MVP — DB only)
- Vue or React (Livewire handles reactivity)
- Pusher (replaced by Reverb)
- Any third-party auth package (no accounts in MVP)
- Any job queues or background workers (not needed in MVP)

---

## Absolute Architecture Rules

These rules are non-negotiable. Violating them breaks the entire architecture.

### RULE 1 — Controllers are thin

Controllers do one thing: receive the HTTP request, call one Service method, return a response.

**NEVER put in a Controller:**
- Game logic
- DB queries beyond finding a model
- Phase transition calls
- Action resolution
- Win condition checks
- Broadcast calls

```php
// ✅ CORRECT
class ActionController extends Controller
{
    public function submit(Request $request, ActionService $service)
    {
        $action = $service->submit($request->player(), $request->validated());
        return response()->json(['status' => 'ok']);
    }
}

// ❌ WRONG — logic in controller
class ActionController extends Controller
{
    public function submit(Request $request)
    {
        $player = Player::find($request->player_id);
        $action = NightAction::create([...]);
        $state = GameState::where('room_id', $player->room_id)->first();
        $state->data['actions'][] = $action->id;
        $state->save();
        broadcast(new NightActionSubmitted($action));
    }
}
```

---

### RULE 2 — PhaseManager is the only class that changes game phase

Never write `$state->phase = 'day'` anywhere except inside `PhaseManager::transition()`.

**NEVER transition phase from:**
- Controllers
- Livewire components
- Services
- Event listeners
- Any role or action class

```php
// ✅ CORRECT
$this->phaseManager->transition($state, 'day');

// ❌ WRONG — direct phase mutation
$state->phase = 'day';
$state->save();
```

---

### RULE 3 — Actions are never resolved on submission

When a player submits a night action, store it in `night_actions` with `resolved_at = null`.
Do not process it. Do not apply its effect. Do not check anything beyond basic validation.

Resolution only happens when `ActionResolver::resolve()` is called at dawn.

```php
// ✅ CORRECT — submission just stores
public function submit(Player $player, array $data): NightAction
{
    $this->validate($player, $data);
    return NightAction::create([
        'game_state_id' => $player->room->gameState->id,
        'player_id'     => $player->id,
        'action_type'   => $data['action_type'],
        'target_id'     => $data['target_id'] ?? null,
        'metadata'      => $data['metadata'] ?? null,
    ]);
}

// ❌ WRONG — resolving on submit
public function submit(Player $player, array $data): void
{
    if ($data['action_type'] === 'kill') {
        $target = Player::find($data['target_id']);
        $target->is_alive = false;
        $target->save();
    }
}
```

---

### RULE 4 — Never broadcast directly from Controllers or Services

All real-time updates happen through Laravel Events.
Fire an event. Let the event broadcast itself via `ShouldBroadcast`.

```php
// ✅ CORRECT
event(new NightActionSubmitted($action));

// ❌ WRONG — direct broadcast
Broadcast::channel('room.' . $roomId, $data);
```

---

### RULE 5 — Sensitive data only on private player channels

Role assignments, night action results (Seer inspect, Fox sniff, enchant notification),
and lover identity must only be broadcast on `player.{player_id}` private channels.

**NEVER** broadcast a player's role or private result to:
- `room.{room_id}` (visible to all players)
- Any public channel

---

### RULE 6 — WinConditionChecker runs after every elimination

After every `PlayerEliminated` event and after every vote resolution, `WinConditionChecker::check()`
must run. Do not skip it. Do not assume the game is still running.

```php
// ✅ CORRECT — always check after death
$this->applyDeath($player, $state);
event(new PlayerEliminated($player));
$winner = $this->winChecker->check($state);
if ($winner) {
    event(new GameFinished($state, $winner));
    return;
}
```

---

### RULE 7 — No hardcoded role logic in the Engine

The Game Engine, ActionResolver, and PhaseManager must not contain `if ($role === 'witch')` logic.
Role-specific behavior belongs in role classes and action classes.
The engine calls interfaces — it does not know which role it is dealing with.

```php
// ✅ CORRECT — engine calls interface
foreach ($actions as $action) {
    if ($action->isValid($state)) {
        $action->resolve($state);
    }
}

// ❌ WRONG — hardcoded role logic in engine
foreach ($actions as $action) {
    if ($action->action_type === 'save') {
        $kill = NightAction::where(...)->first();
        $kill->delete();
    }
}
```

---

### RULE 8 — All user-facing strings go through lang files

Never hardcode French or English text in Blade, Livewire, or PHP.
Every string must exist in both `lang/fr/` and `lang/en/`.

```php
// ✅ CORRECT
{{ __('ui.button.vote') }}
{{ __('roles.seer.name') }}

// ❌ WRONG
Vote
Voyante
```

---

### RULE 9 — Death chains must fully resolve before WinConditionChecker runs

When a player dies and triggers a chain (Lover death, Hunter shot, Knight infection),
the entire chain must complete before `WinConditionChecker` runs.
Do not check win conditions mid-chain.

---

### RULE 10 — The narrator is never a player

If `is_narrator = true` on a player record:
- They have no `role_id`
- They do not appear in the player list shown to other players
- They cannot vote
- They cannot submit night actions
- They cannot be targeted
- They only access the narrator dashboard

Never assign a role to a narrator. Never include them in role count or alive player calculations.

---

### RULE 11 — Every request must be verified against ownership and access policy

There are no user accounts, but every request carries a `session_token` cookie that
identifies a player record. That player record defines what the requester is allowed to do.

**Before executing any action, always resolve and verify:**

1. The `session_token` cookie exists and matches a player in the DB
2. That player belongs to the room being acted upon
3. That player has the role/permission required for the action

If any check fails: return `403 Forbidden`. Never silently ignore or redirect.

---

## Ownership & Access Policy

### Identity Model

There are no user accounts. Identity is a `session_token` — a UUID generated at join time,
stored in `players.session_token` and in an `httpOnly` cookie on the player's device.

Every incoming request is identified by resolving:
```php
$player = Player::where('session_token', $request->cookie('session_token'))->firstOrFail();
```

If no matching player is found: `401 Unauthorized`.

---

### Room Access

| Rule | Detail |
|---|---|
| Only players who joined the room may access it | Verify `$player->room_id === $room->id` on every request |
| A player can only be in one room at a time | Enforced at join — reject if player record already has a `room_id` |
| Players cannot access other rooms' game state, actions, or votes | Always scope DB queries to `$player->room_id` |
| A player cannot join the same room twice | Check for existing record with same `session_token` in same room |

```php
// ✅ CORRECT — always scope to the player's room
$state = GameState::where('room_id', $player->room_id)->firstOrFail();

// ❌ WRONG — unscoped, any room_id accepted from request
$state = GameState::find($request->game_state_id);
```

---

### Narrator-Only Actions

The following actions are strictly reserved for the player with `is_narrator = true`
in the room. Any other player attempting these must receive `403 Forbidden`.

| Action | Check |
|---|---|
| Advance phase (Start Night, Start Day, Start Voting, Resolve Night, etc.) | `$player->is_narrator === true` |
| View narrator dashboard | `$player->is_narrator === true` |
| Access live action feed | `$player->is_narrator === true` |
| See all players' roles | `$player->is_narrator === true` |
| Configure role counts in lobby | `$player->is_narrator && $room->status === 'waiting'` |
| Remove a player from lobby | `$player->is_narrator && $room->status === 'waiting'` |
| Start the game | `$player->is_narrator && $room->status === 'waiting'` |

---

### Player-Only Actions

The following actions are strictly reserved for non-narrator players.
A narrator attempting these must receive `403 Forbidden`.

| Action | Check |
|---|---|
| View role card | `$player->is_narrator === false && $player->role_id !== null` |
| Submit a night action | `$player->is_narrator === false && $player->is_alive === true` |
| Submit a vote | `$player->is_narrator === false && $player->is_alive === true && $player->voting_banned === false` |
| View their own night action result | `$player->is_narrator === false` |

---

### Night Action Ownership

A player may only submit a night action for their own role.

| Rule | Detail |
|---|---|
| Player can only submit action for their assigned role | Verify `$player->role->key === expected_role_for_action_type` |
| Player can only submit one action per night per action type | Check for existing unresolved `night_actions` record this round |
| Player can only target alive players | Verify `$target->is_alive === true` |
| Player cannot target themselves (most roles) | Enforce per role — defined in role's `isValid()` |
| Player can only act when their role is being woken | Verify current narrator phase step matches player's role `night_order` |
| Dead players cannot submit actions | Verify `$player->is_alive === true` |

```php
// ✅ CORRECT — ownership check before storing
public function submit(Player $player, array $data): NightAction
{
    if ($player->is_narrator) abort(403);
    if (!$player->is_alive) abort(403);
    if ($player->room->gameState->phase !== 'night') abort(403);

    $target = Player::findOrFail($data['target_id']);
    if ($target->room_id !== $player->room_id) abort(403);  // target must be in same room
    if (!$target->is_alive) abort(403);

    $this->validate($player, $data);
    return NightAction::create([...]);
}
```

---

### Vote Ownership

| Rule | Detail |
|---|---|
| Player can only vote once per voting phase | Check for existing vote record this round |
| Player cannot vote for themselves | Enforce in `VotingService` |
| Player cannot vote for dead players | Verify `$target->is_alive === true` |
| Player cannot vote if `voting_banned = true` | Verify before showing panel and before accepting submission |
| Player cannot vote if they are the narrator | Verify `$player->is_narrator === false` |
| Target must be in the same room | Verify `$target->room_id === $player->room_id` |

---

### WebSocket Channel Authorization

Channel subscriptions are authenticated in `routes/channels.php`.
Every channel has an explicit auth rule. No channel is public.

```php
// Player can only subscribe to their own private channel
Broadcast::channel('player.{playerId}', function ($request, $playerId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player && $player->id === (int) $playerId;
});

// Only narrator of this room
Broadcast::channel('narrator.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player
        && $player->room_id === (int) $roomId
        && $player->is_narrator === true;
});

// Only werewolf-faction players in this room
Broadcast::channel('werewolves.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))
                    ->with('role')->first();
    return $player
        && $player->room_id === (int) $roomId
        && $player->role
        && $player->role->faction === 'werewolves';
});

// Any player in this room (not narrator)
Broadcast::channel('room.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player && $player->room_id === (int) $roomId;
});
```

---

### What Players Can NEVER Access

These are hard boundaries. No exception, no matter how the request is framed.

| Forbidden access | Why |
|---|---|
| Another player's role | Roles are private — only the owner and narrator may know |
| Another player's night action result (Seer result, Fox result, etc.) | Private results are per-player only |
| Another player's submitted night action content | Only narrator sees the live feed |
| Any data from a room they did not join | Cross-room access is always `403` |
| The narrator dashboard | Only `is_narrator = true` players |
| Another player's session token | Never expose tokens in responses or logs |
| Vote identity (who voted for whom) | Votes are secret — only counts visible, never voter identity |

---

### Middleware

All game routes must be protected by a custom `IdentifyPlayer` middleware that:

1. Reads `session_token` from the cookie
2. Resolves the `Player` record
3. Binds it to the request (`$request->setPlayer($player)`)
4. Returns `401` if token is missing or invalid

```php
// app/Http/Middleware/IdentifyPlayer.php
public function handle(Request $request, Closure $next)
{
    $token = $request->cookie('session_token');
    if (!$token) abort(401, 'No session token');

    $player = Player::where('session_token', $token)->first();
    if (!$player) abort(401, 'Invalid session token');

    $request->merge(['_player' => $player]);
    return $next($request);
}
```

Apply to all routes under `/game`, `/action`, `/vote`, `/narrator`.
The join route (`/join/{code}`) is the only route that does not require this middleware —
it is where the session token is created.

---

## What the Agent MUST Always Do

- Read the relevant spec file before implementing any system
- Implement `RoleInterface`, `ActionInterface`, and `FactionInterface` on every new class
- Fire the correct Event after every state change (see Events Reference below)
- Use `PhaseManager::transition()` for every phase change
- Store night actions as pending — never resolve on submit
- Run `WinConditionChecker` after every elimination
- Put all user-facing text in lang files (both FR and EN)
- Validate action submissions server-side in `ActionService` before storing
- Use DB transactions when resolving actions or advancing phases
- Authenticate every WebSocket channel subscription in `routes/channels.php`
- Check `elder_abilities_disabled` in `game_states.data` before applying any village ability
- Check `fox_ability_active` before allowing Fox to submit a sniff action
- Check `wolf_father_used` before allowing Accursed Wolf-Father to convert
- Check `stuttering_judge_used` before showing the second vote trigger button
- Resolve the player from `session_token` cookie on every protected request
- Verify `$player->room_id === $room->id` before any room-scoped action
- Verify `$player->is_narrator === true` before any narrator-only action
- Verify `$player->is_alive === true` before accepting any action or vote submission
- Verify target belongs to the same room before accepting any targeted action
- Apply `IdentifyPlayer` middleware to all routes except `/join/{code}`
- Return `403 Forbidden` (never silently fail) when an ownership check fails

---

## What the Agent MUST NEVER Do

- Put business logic in Controllers
- Transition phase outside of `PhaseManager`
- Resolve night actions on submission
- Broadcast sensitive data (roles, results) on `room.{room_id}`
- Hardcode role names or role logic in the Engine
- Add Redis, Vue, React, Pusher, or any auth package without approval
- Skip `WinConditionChecker` after an elimination
- Assign a role to the narrator
- Include the narrator in player counts or alive player lists
- Hardcode any user-facing string — always use `__()`
- Check win conditions mid-death-chain
- Build App Narrator Mode (deferred — not in MVP)
- Build any ranking, progression, monetization, or cosmetic system
- Expose one player's role or private results to another player's channel
- Allow a dead player to vote, act, or be targeted
- Allow the same player to vote twice in the same round
- Allow Bodyguard to protect the same player two nights in a row
- Allow Witch to use a potion she has already used
- Allow Wolf Hound to choose their side after night 1
- Accept a `room_id` or `game_state_id` from the request body without verifying it matches the player's room
- Expose another player's role, action, or result to anyone other than that player or the narrator
- Expose session tokens in API responses, logs, or broadcasts
- Allow a player to target someone from a different room
- Allow a non-narrator player to access the narrator dashboard or live action feed
- Allow a narrator to submit night actions or votes
- Process any game action without first running `IdentifyPlayer` middleware
- Write decoy activity to the database or fire any event for it
- Show decoy activity on the narrator dashboard or include it in the live action feed
- Use the same decoy type two nights in a row if avoidable
- Trigger Lover death, Hunter shot, or Knight infection when a player disconnects and is force-killed
- Eliminate a random player on a persistent tie vote — no-elimination is always the fallback
- Enable the werewolf Confirm Kill button when werewolves are still split on different targets
- Auto-submit a werewolf kill on any kind of timeout
- Allow Bodyguard protection to block a Little Girl Caught elimination
- Create a new room when the narrator starts a new game — always reuse the existing room
- Change the room locale after room creation
- Let a player override the room locale individually

---

## What the Agent Should Watch Out For

These are the most common mistakes on this type of project:

**1. Witch save and poison ordering**
Witch must be shown the werewolf kill target before deciding to save.
Save is evaluated before poison in the resolver. Both are optional and independent.
Never resolve Witch actions without first providing the kill target to her panel.

**2. Bodyguard does not block everything**
Bodyguard only blocks the werewolf faction kill.
It does NOT block: Witch poison, Hunter shot, White Werewolf solo kill, Big Bad Wolf extra kill.

**3. Elder vote-out disables all village abilities**
If the Elder is voted out, `elder_abilities_disabled = true` in `game_states.data`.
Every role that uses abilities (Seer, Witch, Fox, etc.) must check this flag before acting.
This applies even if Elder survives his first werewolf attack but is later voted out.

**4. Wolf Hound faction is not set at game start**
Wolf Hound's faction is determined at runtime on night 1 via `choose_side` action.
Do not assume their faction before they submit their choice.
Do not add them to the werewolf channel until they explicitly choose werewolf.

**5. Pied Piper win check runs after enchanting, not only after eliminations**
Run `WinConditionChecker` after every enchant action resolves.
A player can win mid-night if the last non-enchanted player gets enchanted.

**6. Angel win window is very narrow**
Angel wins ONLY if eliminated by village vote during round 1.
Death by werewolf kill in round 1 does NOT trigger Angel win.
Vote elimination in round 2+ does NOT trigger Angel win.
Both conditions (vote + round 1) must be true simultaneously.

**7. Accursed Wolf-Father convert replaces the kill entirely**
On the night Wolf-Father uses his ability, the werewolf group does NOT also kill.
The convert is mutually exclusive with the kill for that night.
Wolf-Father chooses: convert OR the group kills normally.

**8. Scapegoat last decree must be collected before elimination**
Scapegoat submits their decree (who can/cannot vote next round) before being removed.
Do not set `is_alive = false` before the decree is submitted.

**9. Devoted Servant swap window closes at role reveal**
The swap must be offered to Devoted Servant before the voted player's role is revealed publicly.
If the role reveal happens first, the swap window is gone.
This means the swap prompt must appear immediately after vote closes, before any announcement.

**10. White Werewolf solo kill cadence**
Night 1: no solo kill. Night 2: available. Night 3: not available. Night 4: available.
Track `white_werewolf_solo_night` counter in `game_states.data`.
The solo kill is optional — White Werewolf may pass without losing future turns.

**11. Knight with Rusty Sword delayed death timing**
Infection is set the night Knight is killed by werewolves (`infected_werewolf_id` in data).
The infected werewolf dies at the START of the NEXT night's resolution (Step 1 in ActionResolver),
before any other actions for that night are processed.
If the infected werewolf dies before the next night (voted out), the infection is cancelled.

**12. Fox loses ability permanently on a wrong sniff — never recovers**
If Fox sniffs 3 players and none are werewolf-faction, `fox_ability_active = false`.
This is permanent for the rest of the game. There is no way to recover the ability.
Wolf Hound who chose werewolf counts as werewolf for Fox detection.
White Werewolf counts as werewolf for Fox detection.

**13. Village Idiot survival must not fire PlayerEliminated**
When Village Idiot is voted out, they survive. Do not fire `PlayerEliminated`.
Fire `VillageIdiotRevealed` instead. Set `voting_banned = true` on their record.
Their role is publicly revealed — this is the only case a living player's role is shown publicly.

**14. Bear Tamer growl is based on seating adjacency, not game logic adjacency**
Seat order must be captured at game start and stored in `game_states.data.seat_order`.
Adjacency = the two players immediately next to Bear Tamer in that seat order array.
If Bear Tamer dies, no more growl announcements — ever.

**15. Two Sisters and Three Brothers need exact player counts**
Two Sisters requires exactly 2 players assigned that role.
Three Brothers requires exactly 3 players assigned that role.
Enforce this as a hard validation rule in lobby role configuration.
Never allow 1 Sister or 2 Brothers.

---


---

## Edge Case Decisions (Locked)

These decisions were explicitly locked during planning. Do not revisit or change them.

---

### Disconnect Mid-Game

- Reconnect window: **2 minutes** from the moment of disconnection
- If player reconnects within 2 minutes with a valid `session_token` cookie: restored silently, game continues
- If player does not reconnect within 2 minutes: player is marked **dead** (`is_alive = false`)
- Death from disconnect does **not** trigger death chain effects (no Lover death, no Hunter shot, no Knight infection)
- It is treated as a silent removal — no public `PlayerEliminated` announcement to other players
- Narrator dashboard shows a "Disconnected" indicator during the 2-minute window
- After the window expires: narrator dashboard updates to show player as dead
- `WinConditionChecker` runs after the forced death, same as any other elimination
- The disconnected player's role is simply removed from play — no special handling
- Track disconnect window in `game_states.data.disconnected_players` as `[{ player_id, disconnected_at }]`

**NEVER** trigger Lover death, Hunter shot, or Knight infection on a disconnect death.

---

### Tie Vote Rule (Locked)

When a vote results in a tie:

1. Tied players are given a short window to **defend themselves** verbally (narrator manages timing)
2. A **second vote** is held among the **same tied candidates only** — other players are not eligible targets this round
3. If the second vote is still tied → **no elimination this round**, game proceeds to next night
4. `WinConditionChecker` still runs after a no-elimination round
5. Scapegoat override applies on the **first tie only** — if Scapegoat is alive, they are eliminated instead and the defend/revote flow does not happen
6. Stuttering Judge second vote is separate — it triggers a new full vote, not a tiebreak

**NEVER** eliminate a random player on a persistent tie. No-elimination is always the fallback.

---

### Werewolf Kill Mechanism (Locked)

The werewolf kill is **narrator-driven**, not timer-driven.

Flow:
1. Narrator verbally prompts werewolves to open their eyes (no app timer)
2. All werewolf-faction players see a **shared kill panel** showing:
   - List of living non-werewolf players as targets
   - Each werewolf's **current selection in real time** (visible to all werewolves only, via `werewolves.{room_id}` channel)
   - A "Confirm Kill" button — only enabled when **all werewolves have selected the same target**
3. Werewolves coordinate silently until all selections match
4. Any werewolf can change their selection at any time before confirmation
5. Once all selections match, any werewolf can tap "Confirm Kill" to submit
6. Narrator waits — **no forced timeout**
7. If narrator advances without a kill submitted: **no kill this night** (narrator's decision)

**NEVER** auto-submit a kill on timeout. **NEVER** enable Confirm Kill when werewolves are split on targets.

---

### Little Girl Caught (Locked)

- Narrator dashboard has a dedicated **"Little Girl Caught"** button
- Visible only when Little Girl is alive AND game is in Night phase during the werewolf wake step
- When pressed: Little Girl is immediately eliminated
- This is a **narrator-triggered elimination** — does NOT count as a werewolf kill
- Death chain still applies (Lover partner dies if linked; no other special effects)
- Bodyguard protection does **not** block this elimination
- Button is hidden during all other phases and wake steps
- Narrator must confirm before executing (confirmation prompt on dashboard)

---

### Ngrok Session Rule (Locked)

- **Never restart Ngrok mid-session.** Restarting changes the tunnel URL — all players lose connection with no recovery path.
- If this happens: host must create a new room. No recovery flow is built.
- Recommended setup order before each game:
  1. `php artisan serve --host=0.0.0.0 --port=8000`
  2. `php artisan reverb:start`
  3. `ngrok http 8000`
  4. Copy Ngrok URL into `.env` as `NGROK_URL`
  5. Create the room — QR is generated from `NGROK_URL`
- Do not build any Ngrok restart recovery flow.

---

### Role Balance — Narrator's Responsibility (Locked)

- Role min/max counts are **recommendations only** — narrator has full control
- App enforces only hard structural rules (Two Sisters = exactly 2, Three Brothers = exactly 3, solo factions = max 1)
- App shows **soft warnings** (non-blocking) for imbalanced configs:
  - More than 1 werewolf per 3 players
  - No village special roles at all
  - Solo faction roles below recommended player count
- Narrator can start the game despite any soft warning

---

### Narrator Decoy Visibility (Locked)

- Narrator sees only: `"X players solving puzzles"` — a single live aggregate count
- Narrator does NOT see: which puzzles, which players, or whether anyone solved theirs
- Count updates in real time via Livewire
- Purely atmospheric — confirms players are occupied, nothing more

---

### Default Locale (Locked)

- Home screen, join screen, room creation screen default to **English**
- On joining a room: client switches to the room's configured locale
- Locale is set once at room creation (`rooms.settings.locale`) — cannot be changed after
- Players cannot override the room locale individually
- EN/FR toggle is available on the home screen only, before joining any room

---

### New Game After Game Over (Locked)

1. Game over screen shown to all players (winning faction, all roles revealed)
2. **"New Game"** button shown to narrator only
3. Narrator taps it → all players return to the **lobby screen** (same room, same players)
4. Room status resets to `waiting`
5. Cleared: `game_states`, `night_actions`, `votes`, `couple_bonds` for this room
6. Player records reset: `role_id = null`, `is_alive = true`, `voting_banned = false`
7. Narrator reconfigures roles and starts fresh
8. Players do not re-scan QR — they are already in the room

**NEVER** create a new room for a new game. Reuse the existing room record.


## Folder Structure

```
app/
 ├── Game/
 │    ├── Engine/
 │    │    ├── GameEngine.php             ← orchestrates game lifecycle
 │    │    ├── PhaseManager.php           ← ONLY class that changes phase
 │    │    ├── ActionResolver.php         ← ONLY place actions are resolved
 │    │    └── WinConditionChecker.php    ← runs after EVERY elimination
 │    │
 │    ├── Roles/
 │    │    ├── RoleInterface.php
 │    │    ├── BaseRole.php
 │    │    ├── Village/
 │    │    ├── Werewolves/
 │    │    └── Neutral/
 │    │
 │    ├── Actions/
 │    │    ├── ActionInterface.php
 │    │    ├── BaseAction.php
 │    │    └── NightAction.php
 │    │
 │    ├── Phases/
 │    │    ├── PhaseInterface.php
 │    │    ├── WaitingPhase.php
 │    │    ├── NightPhase.php
 │    │    ├── DayPhase.php
 │    │    ├── VotingPhase.php
 │    │    └── FinishedPhase.php
 │    │
 │    ├── Factions/
 │    │    ├── FactionInterface.php
 │    │    ├── VillageFaction.php
 │    │    ├── WerewolvesFaction.php
 │    │    ├── LoversFaction.php
 │    │    ├── PiedPiperFaction.php
 │    │    ├── WhiteWerewolfFaction.php
 │    │    └── AngelFaction.php
 │    │
 │    ├── Narration/
 │    │    └── HumanNarratorMode.php
 │    │
 │    └── Services/
 │         ├── LobbyService.php
 │         ├── RoleAssignmentService.php
 │         ├── GameService.php
 │         ├── VotingService.php
 │         └── ActionService.php
 │
 ├── Events/
 │    ├── GameStarted.php
 │    ├── PhaseChanged.php
 │    ├── NightActionSubmitted.php
 │    ├── NightResolved.php
 │    ├── VoteSubmitted.php
 │    ├── PlayerEliminated.php
 │    ├── LoverDied.php
 │    ├── VillageIdiotRevealed.php
 │    └── GameFinished.php
 │
 ├── Models/
 │    ├── Room.php
 │    ├── Player.php
 │    ├── Role.php
 │    ├── GameState.php
 │    ├── NightAction.php
 │    ├── Vote.php
 │    └── CoupleBond.php
 │
 └── Http/
      ├── Controllers/            ← thin only — no logic
      └── Livewire/               ← UI only — no game logic

resources/views/
lang/fr/ + lang/en/
specs/
```

---

## Database Schema

```sql
rooms
  id, code VARCHAR unique, host_player_id nullable FK,
  status ENUM(waiting, playing, finished),
  narration_mode ENUM(human),
  settings JSON, timestamps

players
  id, room_id FK, nickname VARCHAR, session_token VARCHAR unique,
  role_id nullable FK, is_alive BOOL default true,
  is_host BOOL default false, is_narrator BOOL default false,
  voting_banned BOOL default false, timestamps

roles
  id, key VARCHAR unique, faction VARCHAR,
  night_order INT nullable, abilities JSON,
  win_condition VARCHAR, timestamps

game_states
  id, room_id unique FK,
  phase ENUM(waiting, night, day, voting, finished),
  round INT default 1, data JSON, timestamps

night_actions
  id, game_state_id FK, player_id FK, action_type VARCHAR,
  target_id nullable FK, metadata JSON nullable,
  resolved_at nullable timestamp, created_at

votes
  id, game_state_id FK, voter_id FK, target_id FK, created_at

couple_bonds
  id, game_state_id FK, player_id FK, partner_id FK, created_at
```

### game_states.data JSON keys — full reference

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
  "second_vote_triggered": false,
  "pied_piper_eliminated": false,
  "vote_ban_next_round": [],
  "bodyguard_last_protected_id": null,
  "witch_save_used": false,
  "witch_poison_used": false,
  "devoted_servant_used": false,
  "knight_killed_by_werewolf": false
}
```

---

## Events Reference

Every state change must fire the correct event. No exceptions.

| Event | When | Broadcasts to |
|---|---|---|
| `GameStarted` | After role assignment | `room.{id}` |
| `PhaseChanged` | Every phase transition | `room.{id}` |
| `NightActionSubmitted` | Player stores pending action | `narrator.{id}` |
| `NightResolved` | ActionResolver finishes batch | `room.{id}` |
| `VoteSubmitted` | Player stores a vote | `narrator.{id}` |
| `PlayerEliminated` | Player's `is_alive` set to false | `room.{id}` |
| `LoverDied` | Lover death triggers partner death | `room.{id}` |
| `VillageIdiotRevealed` | Village Idiot voted out but survives | `room.{id}` |
| `GameFinished` | WinConditionChecker finds a winner | `room.{id}` |

---

## Reverb Channel Rules

| Channel | Subscribers | Carries |
|---|---|---|
| `room.{room_id}` | All players + narrator | Phase changes, eliminations, public events |
| `narrator.{room_id}` | Narrator only | Live action feed, full player state |
| `player.{player_id}` | That player only | Role, night results, private notifications |
| `werewolves.{room_id}` | Werewolf-faction players | Shared kill target, identity reveal |

All channels are private. Channel auth in `routes/channels.php`. Never use public channels.

---

## Interface Contracts

### RoleInterface
```php
interface RoleInterface {
    public function getKey(): string;
    public function getName(string $locale): string;
    public function getFaction(): string;
    public function getNightOrder(): ?int;
    public function getAbilities(): array;
    public function getWinCondition(): string;
    public function hasNightAction(): bool;
}
```

### ActionInterface
```php
interface ActionInterface {
    public function getActingRole(): string;
    public function getTarget(): ?Player;
    public function isValid(GameState $state): bool;
    public function resolve(GameState $state): void;
    public function getPriority(): int;
}
```

### FactionInterface
```php
interface FactionInterface {
    public function getKey(): string;
    public function getName(string $locale): string;
    public function checkWin(GameState $state): bool;
    public function getWinners(GameState $state): Collection;
}
```

---

## Lovers Rules (Locked)

| Scenario | Outcome |
|---|---|
| Both lovers same faction, both alive when faction wins | Faction wins — no override |
| Lovers are cross-faction | Werewolf faction wins when they reach win condition |
| One lover dies (any cause) | Partner dies immediately — full chain resolves |
| Dying lover is Hunter | Hunter fires before partner death |

---

## Win Condition Priority

Checked after every elimination and every vote resolution, in this order:

1. Angel (vote elimination in round 1 only)
2. White Werewolf (last player standing alone)
3. Pied Piper (all living players enchanted)
4. Werewolves (parity with village-aligned players reached)
5. Village (all werewolf-faction players dead)
6. Lovers (cross-faction, both survived — last resort)

---

## Night Action Resolution Order (ActionResolver only)

1. Knight with Rusty Sword delayed death (if `infected_werewolf_id` set)
2. Bodyguard protection (mark protected player in resolver context)
3. Werewolf kill (cancelled if protected or Wolf-Father converting this night)
4. Big Bad Wolf extra kill (only if no werewolf has died yet)
5. Accursed Wolf-Father conversion (replaces kill — mutually exclusive)
6. White Werewolf solo kill (every other night, optional)
7. Witch save (cancels werewolf kill on same target only)
8. Witch poison (independent kill — not cancellable)
9. Pied Piper enchant (run win check after)
10. Fox sniff (result only — private broadcast)
11. Seer inspect (result only — private broadcast)

---

## Narrator Capabilities

| Can do | Cannot do |
|---|---|
| See all roles and live action feed | Override or cancel submitted actions |
| Control all phase transitions | Reveal roles to players publicly |
| Configure and start the game | Vote |
| Remove players before game starts | Submit night actions |
| Read narration prompt cards | Be targeted by any action |

---

## Night Decoy System

### Purpose

During the night phase, players with an active night action are visibly interacting with
their screen. Players with no night action this round sit idle — which reveals information
to observant players ("Thomas isn't doing anything, he must be a Villager").

The decoy system gives every non-acting alive player a fake task to complete, so all
players look equally busy from the outside. It is purely a social cover mechanic.

### Who Gets a Decoy

Any alive player who meets ALL of these conditions during the night phase:
- `is_alive === true`
- `is_narrator === false`
- Has no pending night action to submit this round (their role has no night action,
  OR their role has already been woken and submitted, OR their role is not active this round)

The narrator never sees decoys. Decoy activity is invisible to the dashboard.

### Decoy Types

A random type is selected each night. Types rotate — the same type should not repeat
on consecutive nights if avoidable. Stored as a stateless client-side random selection;
not persisted to DB.

| Type | Example |
|---|---|
| Math puzzle | "13 × 7 = ?" / "Combien font 13 × 7 ?" |
| Word riddle | "I have cities but no houses. What am I?" / "J'ai des villes mais pas de maisons. Qui suis-je ?" |
| Atmospheric count | Show a dark illustration, ask "How many wolves are hidden?" |
| Letter unscramble | "Unscramble: PUOL-RUAGA" |
| Pattern sequence | "What comes next: 2, 4, 8, 16, ?" |

All decoy content lives in `lang/fr/decoys.php` and `lang/en/decoys.php` as arrays.
Each type has at least 10 entries. A random entry is picked per player per night.

### Behavior Rules

| Rule | Detail |
|---|---|
| No submission required | Player sees the puzzle — no need to answer to proceed |
| Refresh on solve | If player solves it and night is still ongoing, they tap "Next puzzle" to get a new one |
| Refresh is local | New puzzle is drawn client-side from the lang array — no server request |
| Decoy never affects game state | No DB writes, no events, no resolution logic |
| Decoy is masked by default | Same black card face as role card — hold to reveal |
| Narrator never sees it | Decoy activity does not appear in the live action feed |
| Dead players do not get decoys | `is_alive === false` → no night screen at all |

### Implementation Notes

- `DecoyService` or a simple helper generates a random `{type, content}` pair from lang files
- The `NightAction` Livewire component checks: does this player have an active action to submit?
  - Yes → show the real action panel
  - No → show the decoy panel
- "Next puzzle" button calls a Livewire action that returns a new random decoy client-side
- No event is fired. No DB record is written. No server-side state changes.

```php
// ✅ CORRECT — decoy is stateless, client-side only
public function refreshDecoy(): void
{
    // Livewire action — just re-renders with new random content
    $this->decoy = DecoyHelper::random(app()->getLocale());
}

// ❌ WRONG — never track decoy in DB or game state
NightAction::create([
    'action_type' => 'decoy',
    'player_id'   => $player->id,
    ...
]);
```

### What the Agent MUST NEVER Do with Decoys

- Write decoy activity to `night_actions` table
- Fire any event for decoy interactions
- Include decoy submissions in action resolution
- Show decoy activity on the narrator dashboard
- Use decoy answers to infer or expose player roles
- Allow decoy to block or delay real night action submission

---

## Player UI Rules

| Rule | Detail |
|---|---|
| Default state | Always masked (black card face) |
| Reveal gesture | Hold to reveal |
| Hide gesture | Release |
| Maskable elements | Role card, submitted night action, received results, decoy puzzle |
| Anti-misclick | Confirmation required before submitting any action or vote |
| Decoy refresh | "Next puzzle" button available client-side when current puzzle is solved |

---

## MVP Scope Boundaries

### IN SCOPE — build this
- Lobby (create room, QR join, role config, start validation)
- Human Narrator dashboard and phase controls
- All 25+ roles with night actions
- Deferred night resolution with full priority order
- Voting with all edge cases (Scapegoat, Village Idiot, Elder, Stuttering Judge, Devoted Servant)
- Win conditions for all 6 factions
- Lovers bond and death chain
- Mask/unmask system (hold to reveal)
- Night decoy system (math, riddle, atmospheric puzzles for non-acting players)
- Bilingual FR/EN
- Ngrok + SQLite local setup

### OUT OF SCOPE — do not build, do not suggest
- App Narrator Mode
- Ranking or progression systems
- Monetization of any kind
- AI players or AI narration
- Cloud hosting
- Cosmetics or customization
- Replay system
- Statistics or analytics
- Player accounts or authentication
- Online play between strangers not in the same location
