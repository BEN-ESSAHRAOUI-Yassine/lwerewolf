# AGENTS.md — Loup-Garou Companion Platform
# AI Coding Agent Instructions

> This file is the **single source of truth** for any AI coding agent working on this project.
> Read it entirely before writing a single line of code. Follow every rule. Do not skip sections.

---

## 0. Agent Mindset

You are implementing a real-life social deduction game companion app (inspired by Les Loups-Garous de Thiercelieux). Players are physically in the same room. The app manages hidden information, roles, narration, game state, voting, and night actions — while keeping the human social experience at the center.

**Before every task:**
1. Re-read this file in full
2. Identify which layer you are working in (Controller / Service / Engine / Model / Livewire)
3. Confirm you are not violating any Architecture Contract (Section 5)
4. Write the smallest correct unit — no premature abstractions, no gold-plating

---

## 1. Tech Stack

| Layer | Technology | Notes |
|---|---|---|
| Backend framework | Laravel (latest stable) | PHP 8.2+ |
| Frontend templating | Blade | |
| Reactive UI components | Livewire v3 | No Inertia, no Vue, no React |
| CSS framework | TailwindCSS v3 | Dark theme, atmospheric |
| Real-time communication | Laravel Reverb | WebSockets only, no Pusher |
| Local tunnel / hosting | Ngrok | MVP only |
| Language support | Bilingual FR / EN | Laravel lang files |
| Database | MySQL / SQLite (local) | No Redis for MVP |

**Do not introduce any dependency not listed above without asking first.**

---

## 2. Architecture Rules (Non-Negotiable)

```
HTTP Request
     │
     ▼
Controllers          ← thin, delegate immediately — NO logic here
     │
     ▼
Services             ← business logic, orchestration
     │
     ▼
Game Engine          ← state, phases, resolution, win conditions
     │
     ▼
Database             ← single source of truth (DB only, no Redis for MVP)
     │
     ▼
Events               ← fired on every domain state change
     │
     ▼
Reverb Broadcast     ← pushes updates to player/narrator screens via WebSockets
```

### 2.1 Controllers

- Controllers are **thin**. Validate input, call one Service method, return response.
- A controller method must never exceed ~15 lines.
- No game logic, no Eloquent queries, no phase transitions in controllers.

```php
// ✅ Correct
public function submitAction(ActionRequest $request, ActionService $service)
{
    $service->submit($request->validated(), $request->player());
    return response()->noContent();
}

// ❌ Wrong — logic in controller
public function submitAction(Request $request)
{
    $player = Player::find($request->player_id);
    $state = GameState::where('room_id', $player->room_id)->first();
    $state->data['actions'][] = $request->all();
    $state->save();
}
```

### 2.2 Services

- All business logic lives in `app/Game/Services/`.
- Services may call the Game Engine, fire Events, and query/mutate Models.
- Services must not broadcast to WebSocket channels directly — fire an Event instead.

### 2.3 Game Engine

- `PhaseManager` is the **only class** allowed to write `game_states.phase`.
- Never update phase from a Controller, Service, or Livewire component.
- Always call: `PhaseManager::transition(GameState $state, string $toPhase)`

- `WinConditionChecker` runs after **every** elimination and after every vote resolution.
- It checks all factions in priority order and fires `GameFinished` if any win condition is met.

- `ActionResolver` collects all night actions and resolves them as a batch at dawn.
- Resolution order is strictly defined (see Section 7).

### 2.4 Events & Broadcasting

- Fire a Laravel Event for every domain state change (phase change, elimination, vote, action submitted, game over, etc.).
- Events that need real-time delivery must implement `ShouldBroadcast`.
- Sensitive data (roles, night results) must **only** broadcast on `player.{player_id}` private channels.
- Never broadcast role information on `room.{room_id}` (shared channel).

### 2.5 Livewire Components

- Livewire components are **UI only** — they call Controllers or dispatch Livewire actions that call Services.
- They listen to Reverb events and re-render reactively. No direct DB writes.

---

## 3. File & Folder Structure

Respect this structure exactly. Do not rename, relocate, or reorganize.

```
app/
 ├── Game/
 │    ├── Engine/
 │    │    ├── GameEngine.php
 │    │    ├── PhaseManager.php
 │    │    ├── ActionResolver.php
 │    │    └── WinConditionChecker.php
 │    │
 │    ├── Roles/
 │    │    ├── RoleInterface.php
 │    │    ├── BaseRole.php
 │    │    ├── Village/
 │    │    │    ├── Villager.php
 │    │    │    ├── Seer.php
 │    │    │    ├── Witch.php
 │    │    │    ├── Hunter.php
 │    │    │    ├── Bodyguard.php
 │    │    │    ├── LittleGirl.php
 │    │    │    ├── Cupid.php
 │    │    │    ├── Elder.php
 │    │    │    ├── Scapegoat.php
 │    │    │    ├── VillageIdiot.php
 │    │    │    ├── TwoSisters.php
 │    │    │    ├── ThreeBrothers.php
 │    │    │    ├── StutteringJudge.php
 │    │    │    ├── KnightWithRustySword.php
 │    │    │    ├── DevotedServant.php
 │    │    │    ├── BearTamer.php
 │    │    │    └── Fox.php
 │    │    ├── Werewolves/
 │    │    │    ├── Werewolf.php
 │    │    │    ├── BigBadWolf.php
 │    │    │    ├── AccursedWolfFather.php
 │    │    │    ├── WhiteWerewolf.php
 │    │    │    └── WolfHound.php
 │    │    └── Neutral/
 │    │         ├── PiedPiper.php
 │    │         └── Angel.php
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
      ├── Controllers/
      │    ├── LobbyController.php
      │    ├── GameController.php
      │    ├── NarratorController.php
      │    ├── ActionController.php
      │    └── VoteController.php
      └── Livewire/
           ├── Lobby/
           │    ├── CreateRoom.php
           │    └── JoinRoom.php
           ├── Narrator/
           │    ├── NarratorDashboard.php
           │    └── PhaseControls.php
           ├── Player/
           │    ├── RoleCard.php
           │    ├── NightAction.php
           │    └── VotingPanel.php
           └── Shared/
                └── PlayerList.php

resources/views/
 ├── layouts/
 ├── lobby/
 ├── narrator/
 └── player/

lang/
 ├── fr/ → game.php, roles.php, narration.php, ui.php
 └── en/ → game.php, roles.php, narration.php, ui.php
```

---

## 4. Database Schema

Implement migrations **exactly** as specified. Do not add columns not listed here without a comment explaining why.

```sql
rooms
  id
  code                  VARCHAR unique        -- room join code (6 chars, uppercase)
  host_player_id        FK → players.id nullable
  status                ENUM(waiting, playing, finished)
  narration_mode        ENUM(human)
  settings              JSON                  -- role counts, timer config, locale
  created_at, updated_at

players
  id
  room_id               FK → rooms.id
  nickname              VARCHAR
  session_token         VARCHAR unique        -- lightweight identity, stored in cookie
  role_id               FK → roles.id nullable
  is_alive              BOOLEAN default true
  is_host               BOOLEAN default false
  is_narrator           BOOLEAN default false
  created_at, updated_at

roles
  id
  key                   VARCHAR unique        -- e.g. 'werewolf', 'seer', 'witch'
  faction               VARCHAR               -- village | werewolves | lovers | pied_piper | white_werewolf | angel
  night_order           INT nullable          -- null = no night action
  abilities             JSON
  win_condition         VARCHAR
  created_at, updated_at

game_states
  id
  room_id               FK → rooms.id unique
  phase                 ENUM(waiting, night, day, voting, finished)
  round                 INT default 1
  data                  JSON                  -- flexible state (enchanted players, bear growl, etc.)
  created_at, updated_at

night_actions
  id
  game_state_id         FK → game_states.id
  player_id             FK → players.id
  action_type           VARCHAR               -- kill | inspect | save | poison | enchant | etc.
  target_id             FK → players.id nullable
  metadata              JSON nullable
  resolved_at           TIMESTAMP nullable
  created_at

votes
  id
  game_state_id         FK → game_states.id
  voter_id              FK → players.id
  target_id             FK → players.id
  created_at

couple_bonds
  id
  game_state_id         FK → game_states.id
  player_id             FK → players.id
  partner_id            FK → players.id
  created_at
```

---

## 5. Architecture Contracts (Hard Rules)

These are enforced. Violating them requires explicit justification in a code comment.

### RoleInterface — every role must implement this

```php
interface RoleInterface
{
    public function getKey(): string;
    public function getName(string $locale): string;
    public function getFaction(): string;
    public function getNightOrder(): ?int;      // null = no night action
    public function getAbilities(): array;
    public function getWinCondition(): string;
    public function hasNightAction(): bool;
}
```

### ActionInterface — every action must implement this

```php
interface ActionInterface
{
    public function getActingRole(): string;
    public function getTarget(): ?Player;
    public function isValid(GameState $state): bool;
    public function resolve(GameState $state): void;
    public function getPriority(): int;        // used by ActionResolver for ordering
}
```

### FactionInterface — every faction must implement this

```php
interface FactionInterface
{
    public function getKey(): string;
    public function getName(string $locale): string;
    public function checkWin(GameState $state): bool;
    public function getWinners(GameState $state): Collection;
}
```

### PhaseManager Contract

```php
// ✅ Only valid way to change phase
PhaseManager::transition(GameState $state, string $toPhase);

// ❌ Never do this from anywhere
$state->phase = 'night';
$state->save();
```

### WinConditionChecker Contract

```php
// Must be called after EVERY:
// - player elimination
// - vote resolution
WinConditionChecker::check(GameState $state);
```

---

## 6. Real-Time Channel Strategy

| Channel | Type | Carries |
|---|---|---|
| `room.{room_id}` | Private | Phase changes, eliminations, game events |
| `narrator.{room_id}` | Private | Live action feed, full player info |
| `player.{player_id}` | Private | Role card, night action result, seer inspect result |

**Rules:**
- All channels are private (authenticated)
- Roles are **never** sent on `room.{room_id}` — only on `player.{player_id}`
- Narrator channel has full visibility; player channels are scoped to the individual
- Every Livewire component that needs real-time updates must listen to the relevant Reverb channel

---

## 7. Night Action Resolution Order

Actions are collected during Night phase and resolved **as a batch at dawn** by `ActionResolver`.

Strictly follow this priority order:

| Priority | Role | Action |
|---|---|---|
| 1 | Werewolves | kill |
| 2 | Big Bad Wolf | extra kill (only if no werewolf has died yet) |
| 3 | Accursed Wolf-Father | convert instead of kill (once per game) |
| 4 | White Werewolf | kill a werewolf (every other night only) |
| 5 | Bodyguard | protect target |
| 6 | Seer | inspect (result only — no effect on target) |
| 7 | Witch | save potion (cancels werewolf kill) / poison potion (adds a kill) |
| 8 | Pied Piper | enchant |
| 9 | Fox | inspect 3 adjacent players |
| 10 | Cupid | link lovers (night 1 only) |

**Resolution rules:**
- Bodyguard protection is applied before kills are committed
- Witch save cancels the werewolf kill on the **same** target only
- Witch poison is an independent kill — **not** cancellable by Bodyguard
- Seer result is private — broadcast only to `player.{seer_id}`
- If a lover dies during resolution, `LoverDied` fires immediately and the partner dies before resolution continues (death chain completes fully before moving to next action)

---

## 8. Lovers Logic

| Scenario | Outcome |
|---|---|
| Both lovers same faction, both alive when faction wins | Faction wins — no Lovers override |
| Lovers are cross-faction (e.g. villager + werewolf) | Werewolf faction wins if werewolves reach win condition |
| One lover dies (any cause) | Partner dies immediately — death chain resolves fully |
| Dying lover is Hunter | Hunter ability still fires before partner death |

---

## 9. Narrator Mode (MVP — Human Only)

App Narrator Mode is **deferred** — do not implement it.

### Human Narrator

- The narrator is **not a player** and does not receive a role card
- The room creator selects Human Narrator mode and becomes narrator
- Narrator capabilities:
  - Waits for players to join via QR code or room code
  - Configures role counts per role before starting
  - Starts the game when ready
  - Sees full dashboard: all roles, alive/dead status, live action feed
  - Controls all phase transitions manually
  - **Cannot** override or cancel a submitted player action
  - **Never** reveals roles publicly (narrator eyes only)

### Narrator Dashboard Components to Build

- Player list with role + alive/dead status
- Live night action feed (updates in real-time as players submit)
- Phase control buttons: Start Night, Wake [Role], Start Day, Start Voting, Resolve Vote
- Round counter and scrollable game log

---

## 10. Player UI Rules

### Mask / Unmask Mechanic

| Rule | Detail |
|---|---|
| Gesture | Hold to reveal, release to hide |
| Masked default | Black card face — always the default state |
| Maskable elements | Role card, submitted night action, received results (seer result, etc.) |

The player must be able to show their screen to others without leaking sensitive information.
**Default is always masked.**

### UX Constraints

- Maximum **2 taps** to complete any night action
- Dark atmospheric design — medieval village, moonlight, fog, candles
- Discussion happens away from screens — app is a support tool
- Transitions must feel cinematic (use Tailwind transitions + Livewire morphing)
- Optimize readability for **dark rooms** (high contrast, no small text)

---

## 11. Localisation

All user-facing strings use Laravel's localization system. Hard-coded strings in Blade or Livewire are **not allowed**.

```php
// Usage in Blade / Livewire
__('roles.werewolf.name')
__('narration.phase.night_start')
__('ui.button.vote')
```

- Locale is stored in `rooms.settings` JSON
- Locale is set on the session when a player joins the room
- Supported locales: `fr` (default), `en`
- Every lang key must exist in **both** `lang/fr/` and `lang/en/`

---

## 12. MVP Scope — What to Build vs. What to Skip

### ✅ In Scope — Build This

- Local multiplayer via Ngrok tunnel
- QR code + room code joining
- Role assignment (all 25+ roles seeded in DB)
- Role reveal with mask/unmask gesture
- Human narrator dashboard
- Night actions (all roles per Section 7)
- Deferred night resolution via `ActionResolver`
- Voting system with `VotingService`
- Win detection for all factions via `WinConditionChecker`
- Lovers bond and death chain
- Bilingual FR/EN

### ❌ Out of Scope — Do Not Build

- App Narrator Mode (AI-driven narration)
- Ranking or progression systems
- Monetization or payments
- AI players
- Cloud hosting or CI/CD
- Cosmetics or unlockables
- Replay system
- Statistics or analytics

If a feature is not in the ✅ list, do not implement it. Do not add placeholder code, stubs, or TODOs for out-of-scope features.

---

## 13. Implementation Task Order

Follow this sequence. Do not skip ahead. Each phase must be stable before moving to the next.

### Phase 1 — Foundation
- [ ] Laravel project setup with Reverb, Livewire, TailwindCSS
- [ ] Database migrations for all 7 tables
- [ ] Role seeder (all 25+ roles with correct `night_order`, `faction`, `abilities`)
- [ ] `Room`, `Player`, `Role`, `GameState`, `NightAction`, `Vote`, `CoupleBond` models with relationships

### Phase 2 — Lobby
- [ ] `LobbyService` — create room, join room, session token auth
- [ ] `LobbyController` + `CreateRoom` / `JoinRoom` Livewire components
- [ ] QR code generation for room URL
- [ ] `PlayerList` shared Livewire component (live-updating via Reverb)
- [ ] Narrator role assignment (host becomes narrator, no role card)

### Phase 3 — Role Assignment
- [ ] `RoleAssignmentService` — shuffle and assign roles based on narrator config
- [ ] All 25+ Role classes implementing `RoleInterface`
- [ ] `RoleCard` Livewire component with mask/unmask hold gesture

### Phase 4 — Game Engine Core
- [ ] `PhaseManager` with all phase transitions and validation
- [ ] `GameEngine` orchestrating game lifecycle
- [ ] `WinConditionChecker` + all 6 `FactionInterface` implementations
- [ ] All Phase classes (`WaitingPhase`, `NightPhase`, `DayPhase`, `VotingPhase`, `FinishedPhase`)

### Phase 5 — Night Actions
- [ ] `ActionInterface`, `BaseAction`, `NightAction` classes
- [ ] `ActionService` — submit, validate, store night actions
- [ ] `ActionResolver` — deferred batch resolution (Section 7 order)
- [ ] `NightAction` Livewire component (2-tap UX)
- [ ] Lovers death chain in resolver
- [ ] All Events: `NightActionSubmitted`, `NightResolved`, `PlayerEliminated`, `LoverDied`

### Phase 6 — Voting
- [ ] `VotingService` — submit vote, tally, resolve tie, eliminate player
- [ ] `VoteController` + `VotingPanel` Livewire component
- [ ] `WinConditionChecker` call after every vote resolution
- [ ] `VoteSubmitted` and `PlayerEliminated` events

### Phase 7 — Narrator Dashboard
- [ ] `NarratorDashboard` Livewire component (full player list + roles + alive/dead)
- [ ] Live night action feed (via `narrator.{room_id}` Reverb channel)
- [ ] `PhaseControls` Livewire component (all phase control buttons)
- [ ] Game log with round counter

### Phase 8 — Localisation & Polish
- [ ] All lang files in `fr/` and `en/` (game.php, roles.php, narration.php, ui.php)
- [ ] Atmospheric dark UI (cinematic transitions, medieval aesthetic)
- [ ] Readability pass (dark room contrast, touch target sizes)
- [ ] End-to-end smoke test: full game from lobby to win condition

---

## 14. Code Quality Rules

- **No `dd()`, `var_dump()`, or debug statements** in committed code
- **No commented-out code blocks** — delete or keep, not comment
- **No magic strings** — use constants or lang keys
- **Type hints everywhere** — all method parameters and return types
- **Docblocks on public methods** in Engine and Service classes
- Every new class gets a corresponding **Feature or Unit test** (PHPUnit)
- Run `php artisan test` before considering any phase complete
- Run `php artisan migrate:fresh --seed` — must complete without errors

---

## 15. When You Are Unsure

If a requirement is ambiguous:
1. Default to the **simplest correct implementation**
2. Add a `// NOTE:` comment explaining what was ambiguous and what you chose
3. Do **not** invent features — stay within spec
4. Do **not** ask clarifying questions mid-task — make the safest call and note it

---

## 16. Access Control & Anti-Cheat Policy

This section is **mandatory**. Every route, Livewire component, and broadcast channel must enforce these rules. There are no exceptions.

---

### 16.1 Identity Model

A player is identified exclusively by their `session_token` stored in a cookie.

- On every request, resolve the current player via: `Player::where('session_token', $request->cookie('session_token'))->firstOrFail()`
- There is no Laravel Auth / login system — the session token **is** the identity
- If no valid session token is present, redirect to the room join page
- Never trust any player ID, room ID, or role ID coming from the request body or URL without cross-checking it against the resolved player

```php
// ✅ Always resolve identity from cookie, then derive room
$player = Player::where('session_token', $request->cookie('session_token'))->firstOrFail();
$room = $player->room; // derive from player, never from URL alone

// ❌ Never trust the URL / request body directly
$room = Room::find($request->route('room_id'));
```

---

### 16.2 What a Player Can Access

| Resource | Allowed | Condition |
|---|---|---|
| Their own role card | ✅ | `player.room_id` matches the room |
| Their own night action form | ✅ | Player is alive + correct phase |
| Their own voting panel | ✅ | Player is alive + voting phase |
| Their own submitted action result | ✅ | Result belongs to their `player_id` |
| Another player's role card | ❌ | Never |
| Another player's night action | ❌ | Never |
| Another player's result | ❌ | Never |
| A room they did not join | ❌ | Never |
| The narrator dashboard | ❌ | Unless `player.is_narrator = true` |
| Any game state from another room | ❌ | Never |

---

### 16.3 What a Narrator Can Access

| Resource | Allowed | Condition |
|---|---|---|
| Narrator dashboard for their room | ✅ | `player.is_narrator = true` AND `player.room_id` matches |
| Full player list with roles | ✅ | Same room only |
| Live night action feed | ✅ | Same room only |
| Phase controls | ✅ | Same room only |
| Narrator dashboard for another room | ❌ | Never |
| Any player's private channel data | ❌ | Narrator sees aggregate feed, not raw channel data |

---

### 16.4 Enforcement Rules for Every Layer

#### Routes & Controllers

Every controller method that touches game data must start with an ownership check:

```php
// ✅ Required pattern for all game-related controllers
private function resolvePlayerOrRedirect(Request $request): Player
{
    $player = Player::where('session_token', $request->cookie('session_token'))->first();

    if (! $player) {
        return redirect()->route('join');
    }

    return $player;
}

// Then in every method:
public function showRoleCard(Request $request)
{
    $player = $this->resolvePlayerOrRedirect($request);
    // $player->room is safe — derived from identity, not from URL
}
```

#### Livewire Components

- Every Livewire component that renders player-sensitive data must re-validate ownership on every `render()` and on every action method
- Do not cache the player or room in component properties without re-verifying on each lifecycle call
- A Livewire component must never accept a `player_id` or `room_id` as a public property set from outside — always derive from the session token

```php
// ✅ Correct — derive from session token in mount()
public function mount()
{
    $this->player = Player::where('session_token', request()->cookie('session_token'))->firstOrFail();
    abort_if($this->player->room_id !== $this->expectedRoomId, 403);
}

// ❌ Wrong — trusting a passed-in ID
public function mount(int $playerId)
{
    $this->player = Player::find($playerId);
}
```

#### Broadcast Channels

Channel authorization is defined in `routes/channels.php`. Every private channel must verify ownership:

```php
// player.{playerId} — only the owner can listen
Broadcast::channel('player.{playerId}', function ($user, $playerId) {
    $player = Player::where('session_token', request()->cookie('session_token'))->first();
    return $player && $player->id === (int) $playerId;
});

// narrator.{roomId} — only the narrator of that room can listen
Broadcast::channel('narrator.{roomId}', function ($user, $roomId) {
    $player = Player::where('session_token', request()->cookie('session_token'))->first();
    return $player
        && $player->is_narrator
        && $player->room_id === (int) $roomId;
});

// room.{roomId} — only players who belong to that room can listen
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    $player = Player::where('session_token', request()->cookie('session_token'))->first();
    return $player && $player->room_id === (int) $roomId;
});
```

---

### 16.5 Violation Handling — Silent Redirect + Narrator Alert

When a player attempts to access a resource they do not own:

1. **Do not throw a visible error** — silently redirect the player to their own correct page
2. **Do not reveal why** — no error message is shown to the player
3. **Immediately fire a `SuspiciousAccessAttempt` event** — this broadcasts to the narrator's dashboard with the following payload:

```php
// app/Events/SuspiciousAccessAttempt.php
class SuspiciousAccessAttempt implements ShouldBroadcast
{
    public function __construct(
        public readonly ?Player $player,      // null if no valid session token
        public readonly string  $attemptedUrl,
        public readonly string  $attemptType, // 'wrong_room' | 'wrong_player' | 'narrator_only' | 'no_token'
        public readonly string  $ip,
        public readonly string  $timestamp,
    ) {}

    public function broadcastOn(): Channel
    {
        // Broadcast to narrator only if player has a room
        if ($this->player?->room_id) {
            return new PrivateChannel('narrator.' . $this->player->room_id);
        }
        // Otherwise silently log only — no broadcast
        return new NullChannel();
    }
}
```

4. **Log every violation** to Laravel's default log (`Log::warning(...)`) regardless of whether a broadcast is sent
5. The narrator dashboard must display a **live alert feed** alongside the night action feed showing any suspicious access attempts during the game

```php
// In the controller / middleware — the full pattern
$player = Player::where('session_token', $request->cookie('session_token'))->first();

if (! $player || $player->room_id !== $targetRoomId) {
    event(new SuspiciousAccessAttempt(
        player: $player,
        attemptedUrl: $request->fullUrl(),
        attemptType: ! $player ? 'no_token' : 'wrong_room',
        ip: $request->ip(),
        timestamp: now()->toISOString(),
    ));

    // Silent redirect — no error shown to player
    return redirect()->route('player.home', ['token' => $request->cookie('session_token')]);
}
```

---

### 16.6 Dead Players

A dead player (`is_alive = false`) has restricted access:

| Resource | Allowed |
|---|---|
| View the game (spectator read-only view) | ✅ |
| Submit a night action | ❌ |
| Cast a vote | ❌ |
| Interact with any game mechanic | ❌ |

Dead players attempting to submit actions or votes must be silently ignored server-side (no error, no broadcast). Do not rely on UI hiding alone — always enforce on the server.

---

### 16.7 Checklist — Before Merging Any Game Feature

Before any PR touching player data, channels, or game actions is considered complete:

- [ ] Every route resolves identity from session token, never from URL/body alone
- [ ] Every Livewire component verifies ownership in `mount()` and every action method
- [ ] All 3 broadcast channels have authorization defined in `routes/channels.php`
- [ ] `SuspiciousAccessAttempt` event is fired on every ownership violation
- [ ] Dead player restrictions are enforced server-side
- [ ] No player ID, room ID, or role data is exposed in HTML source or JS payloads beyond what the current player is entitled to see

---

*End of AGENTS.md*
