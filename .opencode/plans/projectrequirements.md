# Project Requirements — Loup-Garou Companion Platform

> **Complete specification for rebuilding this project from scratch.**
> Every file, class, route, event, action, migration, lang string, config setting, and Blade template is documented here in full.
> Follow this document in order. Do not skip sections.

---

## 1. Tech Stack

| Layer | Technology | Version Constraint |
|---|---|---|
| Language | PHP | `^8.3` |
| Framework | Laravel | `^13.7` |
| Reactive UI | Livewire | `^4.1` |
| CSS | TailwindCSS | `^4.0` (Vite plugin) |
| Real-time | Laravel Reverb | `^1.10` |
| Client WS | laravel-echo + pusher-js | `^2.3` / `^8.5` |
| QR Code | chillerlan/php-qrcode | `^6.0` |
| Build | Vite | `^8.0` |
| Database | SQLite (file: `database/database.sqlite`) | — |
| Tunnel | ngrok | — |
| Reverse proxy | Caddy | — |

### Package Installation

```bash
# PHP
composer require laravel/framework "^13.7"
composer require livewire/livewire "^4.1"
composer require laravel/reverb "^1.10"
composer require chillerlan/php-qrcode "^6.0"
composer require laravel/tinker "^3.0"

# Dev PHP
composer require --dev laravel/pail "^1.2.5"
composer require --dev laravel/pint "^1.27"
composer require --dev phpunit/phpunit "^12.5"
composer require --dev mockery/mockery "^1.6"
composer require --dev fakerphp/faker "^1.24"
composer require --dev nunomaduro/collision "^8.9"

# JS
npm install vite@"^8.0"
npm install laravel-vite-plugin@"^3.1"
npm install @tailwindcss/vite@"^4.1"
npm install tailwindcss@"^4.0"
npm install axios@"^1.16"
npm install laravel-echo@"^2.3"
npm install pusher-js@"^8.5"
```

---

## 2. Project Setup (Ordered)

```bash
# 1. Create new Laravel project
composer create-project laravel/laravel lwerewolf
cd lwerewolf

# 2. Install all packages from section 1

# 3. Configure .env
cp .env.example .env
# Edit with settings from section 3

# 4. Generate app key
php artisan key:generate

# 5. Install Reverb
php artisan reverb:install

# 6. Create SQLite database
touch database/database.sqlite

# 7. Create all 7 migrations (see section 4)
php artisan migrate

# 8. Create RoleSeeder (see section 20)
php artisan db:seed --class=DatabaseSeeder

# 9. Build frontend
npm install
npm run build

# 10. Start (3 terminals)
# Terminal 1: php artisan serve --host=0.0.0.0 --port=8000
# Terminal 2: php artisan reverb:start
# Terminal 3: npm run dev (optional, hot reload)
```

---

## 3. Environment Configuration

### `.env` — key values

```
APP_NAME="Loup-Garou"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
ASSET_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SESSION_DRIVER=file
SESSION_DOMAIN=null
SESSION_LIFETIME=1440

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=app-id
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### `config/broadcasting.php`

- `default` → `env('BROADCAST_CONNECTION', 'reverb')`
- Connection `reverb` driver with key/secret/app_id from env
- Options: `host`, `port`, `scheme`, `useTLS` from env
- Also keep `pusher`, `ably`, `log`, `null` connections as fallbacks

### `config/auth.php`

Add custom guard:
```php
'guards' => [
    'session-token' => [
        'driver' => 'session-token',
    ],
],
```

### `config/livewire.php`

- `class_namespace` → `App\\Livewire`
- `class_path` → `app_path('Livewire')`
- `view_path` → `resource_path('views/livewire')`
- `component_layout` → `layouts::app`
- `legacy_model_binding` → `false`
- `inject_assets` → `true`

### `config/cors.php`

- `paths` → `['api/*', 'sanctum/csrf-cookie']`
- `allowed_origins` → `['*']`
- `supports_credentials` → `false`

---

## 4. Database Schema (7 migrations)

### `rooms`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| code | VARCHAR(6) | UNIQUE NOT NULL |
| host_player_id | BIGINT | NULLABLE FK → players |
| status | VARCHAR | DEFAULT 'waiting' |
| narration_mode | VARCHAR | DEFAULT 'human' |
| settings | JSON | NULLABLE |
| timestamps | — | created_at, updated_at |

### `players`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| room_id | BIGINT | FK → rooms |
| nickname | VARCHAR | NOT NULL |
| session_token | VARCHAR | UNIQUE NOT NULL |
| role_id | BIGINT | NULLABLE FK → roles |
| is_alive | BOOLEAN | DEFAULT true |
| is_host | BOOLEAN | DEFAULT false |
| is_narrator | BOOLEAN | DEFAULT false |
| voting_banned | BOOLEAN | DEFAULT false |
| timestamps | — | created_at, updated_at |

### `roles`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| key | VARCHAR | UNIQUE NOT NULL |
| faction | VARCHAR | NOT NULL |
| night_order | INT | NULLABLE |
| abilities | JSON | NULLABLE |
| win_condition | VARCHAR | NOT NULL |
| timestamps | — | created_at, updated_at |

### `game_states`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| room_id | BIGINT | UNIQUE FK → rooms |
| phase | VARCHAR | DEFAULT 'waiting' |
| round | INT | DEFAULT 1 |
| data | JSON | NULLABLE |
| timestamps | — | created_at, updated_at |

### `night_actions`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| game_state_id | BIGINT | FK → game_states |
| player_id | BIGINT | FK → players |
| action_type | VARCHAR | NOT NULL |
| target_id | BIGINT | NULLABLE FK → players |
| metadata | JSON | NULLABLE |
| resolved_at | TIMESTAMP | NULLABLE |
| timestamps | — | created_at, updated_at |

### `votes`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| game_state_id | BIGINT | FK → game_states |
| voter_id | BIGINT | FK → players |
| target_id | BIGINT | FK → players |
| timestamps | — | created_at, updated_at |

### `couple_bonds`

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | PK AUTO_INCREMENT |
| game_state_id | BIGINT | FK → game_states |
| player_id | BIGINT | FK → players |
| partner_id | BIGINT | FK → players |
| timestamps | — | created_at, updated_at |

---

## 5. File Tree

```
app/
├── Concerns/PasswordValidationRules.php
├── Concerns/ProfileValidationRules.php
├── Events/                         (19 files — see section 6)
├── Game/
│   ├── Actions/
│   │   ├── ActionInterface.php
│   │   ├── BaseAction.php
│   │   ├── Village/                 (6 action classes)
│   │   ├── Werewolves/              (4 action classes)
│   │   └── Neutral/                 (1 action class)
│   ├── Engine/
│   │   ├── GameEngine.php
│   │   ├── PhaseManager.php
│   │   ├── ActionResolver.php
│   │   └── WinConditionChecker.php
│   ├── Factions/
│   │   ├── FactionInterface.php
│   │   ├── VillageFaction.php
│   │   ├── WerewolvesFaction.php
│   │   ├── LoversFaction.php
│   │   ├── PiedPiperFaction.php
│   │   ├── WhiteWerewolfFaction.php
│   │   └── AngelFaction.php
│   ├── Phases/
│   │   ├── PhaseInterface.php
│   │   ├── WaitingPhase.php
│   │   ├── NightPhase.php
│   │   ├── DayPhase.php
│   │   ├── VotingPhase.php
│   │   └── FinishedPhase.php
│   ├── Roles/
│   │   ├── RoleInterface.php
│   │   ├── BaseRole.php
│   │   ├── Village/                 (17 role classes)
│   │   ├── Werewolves/              (5 role classes)
│   │   └── Neutral/                 (2 role classes)
│   └── Services/
│       ├── ActionService.php
│       ├── LobbyService.php
│       ├── RoleAssignmentService.php
│       └── VotingService.php
├── Helpers/QrHelper.php
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php
│   │   ├── LobbyController.php
│   │   └── VoteController.php
│   └── Middleware/
│       ├── IdentifyPlayer.php
│       └── NgrokHeaders.php
├── Livewire/
│   ├── Lobby/CreateRoom.php
│   ├── Lobby/JoinRoom.php
│   ├── Narrator/NarratorDashboard.php
│   ├── Narrator/NarratorLobby.php
│   ├── Player/NightAction.php
│   ├── Player/PlayerGameView.php
│   ├── Player/PlayerLobby.php
│   ├── Player/RoleCard.php
│   ├── Player/VotingPanel.php
│   └── Shared/PlayerList.php
├── Models/
│   ├── CoupleBond.php
│   ├── GameState.php
│   ├── NightAction.php
│   ├── Player.php
│   ├── Role.php
│   ├── Room.php
│   └── Vote.php
└── Providers/AppServiceProvider.php

bootstrap/app.php
config/                 (14 config files — app, auth, broadcasting, cache, etc.)
database/
├── database.sqlite
└── migrations/         (7 migration files)

lang/
├── en/                 (6 files: ui.php, lobby.php, roles.php, game.php, narration.php, decoys.php)
└── fr/                 (6 files — same structure as en)

resources/
├── css/app.css         (Tailwind v4 + custom theme)
├── js/bootstrap.js     (axios + Echo + Reverb client)
└── views/
    ├── layouts/app.blade.php
    ├── components/placeholder-pattern.blade.php
    ├── partials/head.blade.php
    ├── partials/settings-heading.blade.php
    ├── livewire/lobby/create-room.blade.php
    ├── livewire/lobby/join-room.blade.php
    ├── livewire/narrator/narrator-lobby.blade.php
    ├── livewire/narrator/narrator-dashboard.blade.php
    ├── livewire/player/player-lobby.blade.php
    ├── livewire/player/player-game-view.blade.php
    ├── livewire/player/night-action.blade.php
    ├── livewire/player/voting-panel.blade.php
    ├── livewire/player/role-card.blade.php
    ├── livewire/shared/player-list.blade.php
    └── welcome.blade.php

routes/
├── web.php
├── channels.php
└── console.php
```

---

## 6. Events (19 events — all implement ShouldBroadcast)

| Event | Channel | Payload | Fired By |
|---|---|---|---|
| `PlayerJoined` | `room.{id}` | `{player: {id,nickname,is_narrator}, player_count}` | `LobbyService::joinRoom()` |
| `PlayerLeft` | `room.{id}` | `{player_id, player_count}` | `NarratorLobby::removePlayer()` |
| `SuspiciousAccessAttempt` | `narrator.{id}` | `{player: {id,nickname}, details}` | Any component on auth failure |
| `GameStarted` | `room.{id}` | `{room_id}` | `RoleAssignmentService::assign()` |
| `RoleAssigned` | `player.{id}` | `{role_key, faction, night_order, abilities}` | `RoleAssignmentService` |
| `AllPlayersReady` | `room.{id}` | `{room_id}` | `PlayerGameView::readyUp()` |
| `PhaseChanged` | `room.{id}` | `{phase, round}` | `PhaseManager::transition()` |
| `NightActionSubmitted` | `narrator.{id}` | `{action_id, player_id, action_type, target_id}` | `ActionService::submit()` |
| `NightResolved` | `room.{id}` | `{eliminated: string[]}` | `ActionResolver::resolve()` |
| `PlayerEliminated` | `room.{id}` | `{nickname, role_key, role_name}` | `ActionResolver::applyDeaths()`, `VotingService::resolve()` |
| `LoverDied` | `room.{id}` | `{nickname, partner_nickname}` | `ActionResolver::applyDeaths()` |
| `LoversRevealed` | `room.{id}` | `{message}` | `NarratorDashboard::revealLovers()` |
| `NarratorMessageSent` | `player.{id}` | `{message}` | `NarratorDashboard::sendMessage()` |
| `SeerResultReady` | `player.{id}` | `{target_nickname, faction}` | `SeerInspectAction::resolve()` |
| `FoxResultReady` | `player.{id}` | `{werewolf_found}` | `FoxInspectAction::resolve()` |
| `VillageIdiotRevealed` | `room.{id}` | `{nickname}` | `VotingService::resolve()` |
| `VoteSubmitted` | `narrator.{id}` | `{voter_id, target_id}` | `VotingService::submitVote()` |
| `GameFinished` | `room.{id}` | `{winning_faction, winner_ids}` | `GameEngine::endGame()` |
| `GameReset` | `room.{id}` | `{room_id}` | `NarratorDashboard::newGame()` |

---

## 7. Routes (12 definitions)

### `routes/web.php`

```
GET  /                        → welcome.blade.php          → home
GET  /locale/{locale}         → switches session locale     → locale.switch
GET  /room/{room}/narrator    → NarratorLobby              → lobby.narrator
GET  /room/{room}/player      → PlayerLobby                → lobby.player
GET  /game/{room}/narrator    → NarratorDashboard          → game.narrator
GET  /game/{room}/player      → PlayerGameView             → game.player
GET  /create                  → CreateRoom                  → rooms.create
GET  /join/{code?}            → JoinRoom                    → rooms.join
POST /api/rooms               → LobbyController@create      → api.rooms.create
POST /api/rooms/join          → LobbyController@join        → api.rooms.join
POST /api/vote                → VoteController@submit       → api.vote.submit
```

### `routes/channels.php`

```
player.{playerId}  → $user->id === (int)$playerId                    (guard: session-token)
narrator.{roomId}  → $user->room_id === (int)$roomId && $user->is_narrator  (guard: session-token)
werewolves.{roomId}→ $user->room_id === (int)$roomId && $user->role->faction === 'werewolves'  (guard: session-token)
room.{roomId}      → $user->room_id === (int)$roomId                  (guard: session-token)
```

---

## 8. Middleware & Auth

### `bootstrap/app.php`

```php
$middleware->validateCsrfTokens(except: ['/broadcasting/auth']);
$middleware->appendToGroup('web', IdentifyPlayer::class);
$middleware->prepend(NgrokHeaders::class);
$middleware->trustProxies(at: '*', headers: X_FORWARDED_FOR|X_FORWARDED_HOST|X_FORWARDED_PORT|X_FORWARDED_PROTO);
```

### `IdentifyPlayer` Middleware

Reads `session_token` cookie → finds Player → merges `['_player' => $player]` onto request. Graceful (no abort).

### `NgrokHeaders` Middleware

Adds `ngrok-skip-browser-warning: 1` to every response.

### Auth Guard Registration (in `AppServiceProvider::boot()`)

```php
auth()->viaRequest('session-token', function ($request) {
    $token = $request->cookie('session_token');
    return $token ? Player::where('session_token', $token)->first() : null;
});
```

---

## 9. Models (7 models)

| Model | Fillable | Casts | Key Relations |
|---|---|---|---|
| `Room` | code, host_player_id, status, narration_mode, settings | settings→array | players(), host(), gameState() |
| `Player` | room_id, nickname, session_token, role_id, is_alive, is_host, is_narrator, voting_banned | booleans | room(), role(), nightActions(), votes(), coupleBond() |
| `Role` | key, faction, night_order, abilities, win_condition | night_order→int, abilities→array | players() |
| `GameState` | room_id, phase, round, data | round→int, data→array | room(), nightActions(), votes(), coupleBonds() |
| `NightAction` | game_state_id, player_id, action_type, target_id, metadata, resolved_at | metadata→array, resolved_at→datetime | gameState(), player(), target() |
| `Vote` | game_state_id, voter_id, target_id | — | gameState(), voter(), target() |
| `CoupleBond` | game_state_id, player_id, partner_id | — | gameState(), player(), partner() |

Room uses `getRouteKeyName(): 'code'` for route model binding on room code.

---

## 10. Architecture Rules (Non-negotiable)

1. **Controllers are thin** — one Service call, return response. No game logic.
2. **PhaseManager is the only class that changes `$state->phase`** — never write it directly.
3. **Actions are never resolved on submission** — store with `resolved_at=null`. Only `ActionResolver::resolve()` applies effects.
4. **No direct broadcasts from Controllers/Services** — fire events, let `ShouldBroadcast` handle it.
5. **Sensitive data only on `player.{id}` channels** — roles, results, lover info. Never on `room.{id}`.
6. **WinConditionChecker runs after every elimination** — no skipping.
7. **No hardcoded role logic in Engine** — Engine calls interfaces, not concrete roles.
8. **All strings through lang files** — both EN and FR.
9. **Death chains fully resolve before WinConditionChecker** — Hunter → Lover → chain complete → then win check.
10. **Narrator is never a player** — no role, no vote, no actions, no targeting.
11. **Every request verified against ownership + access policy** — session_token → Player → room check → permission check → 403.

---

## 11. Services

### `LobbyService`
- `createRoom(nickname, locale)`: Creates Room + host Player, sets session_token cookie
- `joinRoom(room, nickname, request)`: Validates, creates Player, sets cookie, fires `PlayerJoined`
- `assignNarrator(room, player)`: Sets is_narrator=true
- `validateGameStart(room)`: Returns error strings array

### `RoleAssignmentService`
- `assign(room)`: Creates GameState, assigns roles from room.settings.role_counts, fires `RoleAssigned` per player, `GameStarted`

### `ActionService`
- `submit(player, data)`: Validates ownership/alive/phase/role→action/duplicate, stores NightAction, fires `NightActionSubmitted`, updates action_history in state.data

### `VotingService`
- `submitVote(player, target, state)`: Validates, stores Vote, fires `VoteSubmitted`
- `tally(state)`: Returns `[target_id => count]`
- `resolve(state)`: Processes votes — Village Idiot survival, Scapegoat tie, Devoted Servant swap, Stuttering Judge second vote, Angel check, Elder vote-out, Hunter shot, Lover chain, win check

---

## 12. Game Engine

### `GameEngine`
- `startGame(room)` → delegates to `RoleAssignmentService::assign()`
- `advancePhase(state, toPhase)` → delegates to `PhaseManager::transition()`
- `resolveVote(state)` → delegates to `VotingService::resolve()`, then transitions
- `resolveNight(state)` → delegates to `ActionResolver::resolve()`, win check, transition
- `eliminatePlayer(player, state)` → sets is_alive=false, fires `PlayerEliminated`, win check
- `endGame(state, winner)` → sets winning_faction, transitions to finished, fires `GameFinished`
- Note: `ActionResolver` lazily resolves `GameEngine` via `app()` to avoid circular dependency

### `PhaseManager`
- `transition(state, toPhase)`: Validates transition. When leaving voting: deletes votes, increments round if going→night. Fires `PhaseChanged`.
- Valid transitions: `waiting→night`, `night→day/finished`, `day→voting`, `voting→night/day/finished`

### `ActionResolver`
- Action class map: `kill→WerewolfKillAction, extra_kill→BigBadWolfKillAction, convert→AccursedWolfFatherConvertAction, solo_kill→WhiteWerewolfKillAction, protect→BodyguardProtectAction, inspect→SeerInspectAction, save→WitchSaveAction, poison→WitchPoisonAction, enchant→PiedPiperEnchantAction, sniff→FoxInspectAction, link_lovers→CupidLinkAction`
- Resolution order (see section 14)
- `applyDeaths()`: Hunter shoots → mark dead → PlayerEliminated → win check → Lover chain (recurse)
- `applyKnightInfection()`: Kills infected_werewolf_id if set

### `WinConditionChecker`
- Priority order: Angel → WhiteWerewolf → PiedPiper → Werewolves → Village → Lovers
- Returns first winning `FactionInterface` or null

---

## 13. Actions (11 classes — priority order)

| Priority | Class | Action Type | Effect |
|---|---|---|---|
| 1 | `CupidLinkAction` | `link_lovers` | Links two lovers (round 1 only, once per game) |
| 2 | `BodyguardProtectAction` | `protect` | Marks protected player (cannot repeat same target) |
| 3 | `WerewolfKillAction` | `kill` | Sets werewolf_kill_target_id |
| 4 | `BigBadWolfKillAction` | `extra_kill` | Extra kill (only if no wolf-faction has died) |
| 5 | `AccursedWolfFatherConvertAction` | `convert` | Converts target to werewolf (replaces kill) |
| 6 | `WhiteWerewolfKillAction` | `solo_kill` | Solo kill every other night (even rounds) |
| 7 | `WitchSaveAction` | `save` | Cancels werewolf kill on target (once per game) |
| 8 | `WitchPoisonAction` | `poison` | Independent kill (once per game) |
| 9 | `PiedPiperEnchantAction` | `enchant` | Adds target to enchanted_player_ids |
| 10 | `FoxInspectAction` | `sniff` | Sniffs 3 players, disables if wrong, fires `FoxResultReady` |
| 11 | `SeerInspectAction` | `inspect` | Inspects faction, fires `SeerResultReady` |

---

## 14. Night Resolution Order

1. Knight with Rusty Sword delayed death (`infected_werewolf_id`)
2. Bodyguard protection mark
3. Werewolf kill (cancelled if protected or Wolf-Father converting)
4. Big Bad Wolf extra kill
5. Accursed Wolf-Father conversion (replaces kill — mutually exclusive)
6. White Werewolf solo kill (optional, every other night)
7. Witch save (cancels werewolf kill on same target)
8. Witch poison (independent, not cancellable)
9. Pied Piper enchant (run win check after)
10. Fox sniff (private broadcast)
11. Seer inspect (private broadcast)

---

## 15. Factions (6 classes — win condition priority order)

1. **AngelFaction** — Angel eliminated by vote in round 1
2. **WhiteWerewolfFaction** — Last player standing is White Werewolf
3. **PiedPiperFaction** — All alive players enchanted
4. **WerewolvesFaction** — Parity with village-aligned (werewolves ≥ village, werewolves > 0)
5. **VillageFaction** — No werewolf-faction players alive
6. **LoversFaction** — Only 2 alive, cross-faction lovers with bond

---

## 16. Phases (5 classes)

| Phase | Night Actions | Voting | Discussion | Narrator Controls |
|---|---|---|---|---|
| `waiting` | No | No | Yes | start_game |
| `night` | Yes | No | No | resolve_night, little_girl_caught, reveal_lovers |
| `day` | No | No | Yes | start_voting |
| `voting` | No | Yes | No | resolve_vote, trigger_second_vote |
| `finished` | No | No | Yes | new_game |

---

## 17. Roles (24 classes)

### Village (17)
`villager, seer, witch, hunter, bodyguard, little_girl, cupid, elder, scapegoat, village_idiot, two_sisters, three_brothers, stuttering_judge, knight_with_rusty_sword, devoted_servant, bear_tamer, fox`

### Werewolves (5)
`werewolf, big_bad_wolf, white_werewolf, wolf_hound, accursed_wolf_father`

### Neutral (2)
`pied_piper, angel`

Each implements `RoleInterface`: `getKey()`, `getName(locale)`, `getFaction()`, `getNightOrder()`, `getAbilities()`, `getWinCondition()`, `hasNightAction()`

---

## 18. Livewire Components (10)

| Component | Route | Key Listeners |
|---|---|---|
| `CreateRoom` | `/create` | — |
| `JoinRoom` | `/join/{code?}` | — |
| `NarratorLobby` | `/room/{room}/narrator` | PlayerJoined, PlayerLeft |
| `PlayerLobby` | `/room/{room}/player` | PlayerJoined, PlayerLeft, GameStarted |
| `NarratorDashboard` | `/game/{room}/narrator` | PhaseChanged, PlayerEliminated, NightActionSubmitted, VoteSubmitted, SuspiciousAccessAttempt, GameFinished, AllPlayersReady |
| `PlayerGameView` | `/game/{room}/player` | PhaseChanged, PlayerEliminated, NightResolved, LoverDied, VillageIdiotRevealed, GameFinished, GameReset, RoleAssigned, SeerResultReady, FoxResultReady, LoversRevealed, NarratorMessageSent |
| `NightAction` | (embedded) | — |
| `VotingPanel` | (embedded) | PhaseChanged |
| `RoleCard` | (embedded) | — |
| `PlayerList` | (embedded) | PlayerJoined, PlayerLeft |

---

## 19. Blade Templates Summary

| Template | Purpose |
|---|---|
| `layouts/app.blade.php` | Dark theme layout, Cinzel/Inter fonts, fog/vignette, Vite, Livewire scripts |
| `welcome.blade.php` | Home page with locale toggle + create/join buttons |
| `livewire/lobby/create-room.blade.php` | Nickname input + submit |
| `livewire/lobby/join-room.blade.php` | Code + nickname inputs + submit |
| `livewire/narrator/narrator-lobby.blade.php` | QR, player list with Remove, role config +/- buttons, validation, start |
| `livewire/narrator/narrator-dashboard.blade.php` | Phase controls, player grid (role/badges/actions), vote tally, relations, action history, log, message modal, new game |
| `livewire/player/player-lobby.blade.php` | Room code, nickname, player list, waiting spinner |
| `livewire/player/player-game-view.blade.php` | Phase overlay, result notifications (6 types), role card, last night deaths, Seer/Fox result card (hold-to-reveal), lover info, night action, voting, ready-up, game over |
| `livewire/player/night-action.blade.php` | Target selection with Cupid 2-step, decoy, confirmation, mask/unmask |
| `livewire/player/voting-panel.blade.php` | Live tally, target list, confirmation, banned state |
| `livewire/player/role-card.blade.php` | Hold-to-reveal role card (masked: "?" / revealed: faction+name+description) |
| `livewire/shared/player-list.blade.php` | Simple player nickname list |

---

## 20. CSS Theme (`app.css`)

```css
@import 'tailwindcss';
@source '../views';
@custom-variant dark (&:where(.dark, .dark *));
```

### Custom Theme Colors:
```
--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif
--font-serif: 'Cinzel', ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif

--color-bg-primary: #0D0D0D
--color-bg-surface: #1A1510
--color-bg-elevated: #251E16
--color-text-primary: #E8D9B5
--color-text-secondary: #9A8A6A
--color-accent-warm: #C8922A
--color-accent-danger: #8B2020
--color-accent-village: #3A6B3A
--color-accent-neutral: #5A5A8A
--color-accent-lovers: #8B4A6B
--color-masked-card: #000000
--color-dead-player: #3A3530
```

### Key Classes:
- `.phase-overlay`, `.phase-overlay-night/day/voting/finished` — full screen phase transition animations
- `.card-masked`, `.card-revealed` — gradient backgrounds for role cards
- `.fog-layer`, `.vignette` — atmospheric effects
- Custom keyframes: phaseOverlayIn, fogDrift, candleFlicker, pulseGlow, elementFadeIn, bellToll

---

## 21. Role Seeder Data

Insert 24 rows into `roles` table:

| key | faction | night_order | win_condition |
|---|---|---|---|
| villager | village | null | village |
| seer | village | 9 | village |
| witch | village | 10 | village |
| hunter | village | null | village |
| bodyguard | village | 2 | village |
| little_girl | village | null | village |
| cupid | village | 1 | village |
| elder | village | null | village |
| scapegoat | village | null | village |
| village_idiot | village | null | village |
| two_sisters | village | null | village |
| three_brothers | village | null | village |
| stuttering_judge | village | null | village |
| knight_with_rusty_sword | village | null | village |
| devoted_servant | village | null | village |
| bear_tamer | village | 13 | village |
| fox | village | 12 | village |
| werewolf | werewolves | 3 | werewolves |
| big_bad_wolf | werewolves | 4 | werewolves |
| accursed_wolf_father | werewolves | 5 | werewolves |
| white_werewolf | white_werewolf | 6 | white_werewolf |
| wolf_hound | werewolves | null | werewolves |
| pied_piper | pied_piper | 11 | pied_piper |
| angel | angel | null | angel |

---

## 22. game_states.data JSON Reference

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
  "bodyguard_protected_id": null,
  "witch_save_used": false,
  "witch_poison_used": false,
  "devoted_servant_used": false,
  "knight_killed_by_werewolf": false,
  "werewolf_kill_target_id": null,
  "big_bad_wolf_target_id": null,
  "wolf_father_convert_target_id": null,
  "white_werewolf_solo_target_id": null,
  "witch_save_target_id": null,
  "witch_poison_target_id": null,
  "players_ready": [],
  "last_night_deaths": [],
  "action_history": [],
  "seer_results": {},
  "fox_results": {},
  "lover_info": {},
  "winning_faction": null,
  "angel_eliminated_by_vote": false
}
```

---

## 23. Client JS (`bootstrap.js`)

```js
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
});
```

---

## 24. Caddy Setup (for ngrok reverse proxy with Reverb)

```
:8082

handle /app/* {
    reverse_proxy localhost:8080
}

handle {
    reverse_proxy localhost:8000 {
        header_up X-Forwarded-Proto https
        header_up X-Forwarded-Host {host}
    }
}
```

- Laravel on port 8000, Reverb on port 8080
- Caddy on port 8082 routes `/app/*` → Reverb, everything else → Laravel
- ngrok tunnels to Caddy port 8082

---

## 25. Run Commands

```bash
# Development (3 terminals)
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start
npm run dev        # or: npm run build

# After code changes
composer dump-autoload
php artisan config:clear
php artisan optimize
npm run build
```

---

## 26. Edge Case Decisions (Locked — do not revisit)

- **Disconnect**: 2-min reconnect window. After: silent death. No death chain effects.
- **Tie vote**: Defend → revote (tied candidates only). Still tied → no elimination. Scapegoat overrides first tie. No random elimination.
- **Werewolf kill**: Narrator-driven (no timer). All wolves must match target before Confirm Kill enabled. No auto-submit.
- **Little Girl Caught**: Narrator button during werewolf wake. Immediate elimination. Not blocked by Bodyguard.
- **Ngrok**: Never restart mid-session. No recovery flow.
- **Role balance**: Narrator's responsibility. App only enforces: Two Sisters=2, Three Brothers=3, solo factions≤1.
- **New game**: Reuses existing room. Clears state, actions, votes, bonds. Players reset. All return to lobby.
- **Night decoys**: Client-side only. No DB writes, no events, no narrator visibility.
- **Hunter-lover chain**: Use `else` block (not `continue`) — Hunter fires THEN partner dies.
- **Stale state fix**: `render()` and all Echo handlers re-read state from DB: `GameState::where('room_id', ...)->first()`.

---

## 27. MVP Boundaries

### In Scope (build this):
- Lobby (create room, QR join, role config, start validation)
- Human Narrator dashboard + phase controls
- All 24+ roles with night actions
- Deferred night resolution with full priority order
- Voting with all edge cases
- Win conditions for all 6 factions
- Lovers bond + death chain
- Mask/unmask (hold to reveal)
- Night decoy system
- Bilingual FR/EN
- Ngrok + SQLite local setup

### Out of Scope (do not build):
- App Narrator Mode (AI narration)
- Ranking, progression, monetization
- AI players
- Cloud hosting
- Cosmetics/customization
- Replay system
- Statistics/analytics
- Player accounts/authentication
- Online play between strangers

---

## 28. Ownership & Access Policy

| Action | Check | Returns |
|---|---|---|
| Create room | Any visitor | — |
| Join room | Valid code, unique nickname | — |
| View narrator lobby | is_narrator=true + belongs to room | 403/redirect |
| View player lobby | is_narrator=false + belongs to room | 403/redirect |
| View narrator dashboard | is_narrator=true + room playing | 403/redirect |
| View player game view | is_narrator=false + room playing | 403/redirect |
| Submit night action | Alive, not narrator, correct phase, owns role, no duplicate | 403 |
| Submit vote | Alive, not narrator, not voting_banned, voting phase, one per round, not self | 403 |
| Narrator: advance phase | is_narrator=true | 403 |
| Narrator: send message | is_narrator=true | 403 |
| Narrator: kick player | is_narrator=true | 403 |
| Narrator: new game | is_narrator=true + finished | 403 |
| View role card | Owner only | 403 |
| View night results | Owner only | 403 |
| View lover info | Owner only | 403 |

Every violation fires `SuspiciousAccessAttempt` event.

---

## 29. Important Implementation Notes

1. `$state->data` is a cast array — read into local var, modify, assign whole array back on save
2. `getListeners()` uses `$this->player->id` / `$this->room->id` — must be available at mount time
3. Echo event names in Livewire use short class name (e.g., `PhaseChanged`), not FQCN
4. `render()` must re-read state from DB — do NOT rely on `$this->state` being fresh
5. Vote clearing happens in `PhaseManager::transition()` when leaving `voting` phase
6. Cupid round-1-only enforced in 4 places: NightAction::mount/confirmSubmit, ActionService::submit, CupidLinkAction::isValid
7. Hunter-lover chain uses `else` block (not `continue`) to fire Hunter shot THEN partner death
