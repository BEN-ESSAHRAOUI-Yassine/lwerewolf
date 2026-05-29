# Checkpoint — May 29, 2026

> Read this file before making any changes. It documents the current state,
> what was fixed, what's still broken, and where to continue.

---

## Project Overview

Real-life social deduction companion app (Les Loups-Garous de Thiercelieux).
Players are physically together. App manages hidden information, roles, narration,
game state, voting, and night actions.

### Tech Stack
| Layer | Technology |
|---|---|
| Backend | Laravel (PHP 8.x) |
| Templating | Blade |
| Reactive UI | Livewire v3 |
| CSS | TailwindCSS |
| Real-time | Laravel Reverb (WebSocket) |
| Tunnel | Ngrok (free plan) |
| Database | SQLite |
| Languages | FR / EN via `lang/` files |

---

## Architecture Rules (from AGENTS.md)

These are enforced by the codebase and must never be violated:

1. **Controllers are thin** — call one Service method, return response
2. **PhaseManager is the ONLY class that changes `$state->phase`** — never write `$state->phase = 'day'` elsewhere
3. **Actions are NEVER resolved on submission** — store in `night_actions` with `resolved_at = null`
4. **Never broadcast directly from Controllers or Services** — fire Events, let `ShouldBroadcast` handle it
5. **Sensitive data only on private player channels** — `player.{id}`, never `room.{id}`
6. **WinConditionChecker runs after every elimination**
7. **No hardcoded role logic in the Engine** — role classes implement interfaces
8. **All user-facing strings go through `__()` lang files** (FR + EN)
9. **Death chains fully resolve before WinConditionChecker runs**
10. **Narrator is NEVER a player** — `is_narrator = true` means no role, no vote, no night actions
11. **Ownership check on EVERY request** — session_token → player → room → permission → 403 if fail

---

## Current Running State

| Process | Status | Port | Command |
|---|---|---|---|
| Laravel dev server | ✅ Running | 8000 | `php artisan serve --host=0.0.0.0 --port=8000` |
| Reverb WebSocket | ✅ Running | 8080 | `php artisan reverb:start` |
| Ngrok tunnel | ✅ Running | 8000→443 | `ngrok start laravel` (single tunnel for Laravel only) |
| Vite production build | ✅ Built | — | Last built: May 29 |

### Ngrok Details
- URL: `https://crunchy-junkie-army.ngrok-free.dev`
- Config: `C:\Users\admin\AppData\Local\ngrok\ngrok.yml` (single tunnel, Laravel only)
- **Free plan limitation**: 1 URL per account. WebSocket (`wss://`) cannot reach Reverb through Ngrok.
- **Workaround**: Livewire polling (`wire:poll.3s`) on components that need real-time updates.
  Echo listeners remain in the code — they activate automatically when WebSocket IS reachable
  (LAN users via `http://192.168.100.142:8000`).

---

## File Map (Key Files)

### `/app/Game/` — Core Game Logic

| File | Purpose | Status |
|---|---|---|
| `Engine/GameEngine.php` | Orchestrates startGame, resolveNight, resolveVote, eliminatePlayer, endGame | Complete |
| `Engine/PhaseManager.php` | Phase transitions (`waiting→night→day→voting→finished`), validates transitions | Complete |
| `Engine/ActionResolver.php` | 11-step night action resolution (priority order) | Complete |
| `Engine/WinConditionChecker.php` | Checks win conditions after eliminations (6 factions) | Complete |
| `Services/RoleAssignmentService.php` | Assigns roles, creates GameState, fires GameStarted + RoleAssigned events | Complete |
| `Services/LobbyService.php` | joinRoom, validateGameStart | Complete |
| `Services/VotingService.php` | Vote resolution | **Stub** |
| `Services/ActionService.php` | Night action submission validation | **Likely incomplete** |
| `Roles/RoleInterface.php` | Interface contract for roles | Complete |
| `Roles/BaseRole.php` | Base role class | Complete |
| `Roles/Village/` | All village role classes | Complete |
| `Roles/Werewolves/` | All werewolf role classes | Complete |
| `Roles/Neutral/` | All neutral role classes | Complete |
| `Actions/ActionInterface.php` | Interface contract for actions | Complete |
| `Actions/BaseAction.php` | Base action class | Complete |
| `Actions/NightAction.php` | NightAction model | Complete |
| `Factions/*.php` | All 6 faction classes with win checks | Complete |

### `/app/Livewire/` — UI Components

| Component | File | Purpose | Status |
|---|---|---|---|
| `Narrator/NarratorLobby` | `NarratorLobby.php` | Create room, QR, role config, start game | **Fixed: wire:poll + validateConfig** |
| `Narrator/NarratorDashboard` | `NarratorDashboard.php` | Phase controls, player management | **Stub — needs full impl** |
| `Player/PlayerGameView` | `PlayerGameView.php` | Player game view with Echo listeners | **Stub — needs full impl** |
| `Player/NightAction` | `NightAction.php` | Night action submission | **Stub — needs full impl** |
| `Shared/PlayerList` | `PlayerList.php` | Real-time player list | **Fixed: refreshPlayers in render** |

### `/app/Events/` — Broadcast Events

| Event | File | Channels | Status |
|---|---|---|---|
| `GameStarted` | `GameStarted.php` | `room.{id}` | Complete |
| `PhaseChanged` | `PhaseChanged.php` | `room.{id}` | Complete |
| `NightActionSubmitted` | `NightActionSubmitted.php` | `narrator.{id}` | Complete |
| `NightResolved` | `NightResolved.php` | `room.{id}` | Complete |
| `VoteSubmitted` | `VoteSubmitted.php` | `narrator.{id}` | Complete |
| `PlayerEliminated` | `PlayerEliminated.php` | `room.{id}` | Complete |
| `PlayerJoined` | `PlayerJoined.php` | `room.{id}` | Complete |
| `PlayerLeft` | `PlayerLeft.php` | `room.{id}` | Complete |
| `RoleAssigned` | `RoleAssigned.php` | `player.{id}` | Complete |
| `GameFinished` | `GameFinished.php` | `room.{id}` | Complete |
| `LoverDied` | `LoverDied.php` | `room.{id}` | Complete |
| `VillageIdiotRevealed` | `VillageIdiotRevealed.php` | `room.{id}` | Complete |

### Routes

| File | Purpose |
|---|---|
| `routes/web.php` | Web routes (home, create, join, game, narrator, etc.) |
| `routes/channels.php` | WebSocket channel auth (room, player, narrator, werewolves) |

### Config

| File | Key Settings |
|---|---|
| `.env` | `APP_URL=crunchy-junkie-army.ngrok-free.dev`, `BROADCAST_CONNECTION=reverb`, `REVERB_HOST=localhost:8080` |
| `config/broadcasting.php` | Default: `reverb`, connections: `reverb` + `null` |
| `config/reverb.php` | Server on `0.0.0.0:8080`, app key `wolf-key`, allowed origins `*` |
| `config/livewire.php` | `asset_url` commented out (uses relative paths) |
| `bootstrap/app.php` | `trustProxies(*)`, middleware aliases, channels registration |

---

## Session May 29 — What Was Fixed

### Issue 1: Start Game returns HTTP 500

**When**: Clicking "Start Game" after configuring roles.

**Root cause**: `GameEngine::startGame()` runs inside `DB::transaction()`.
It fires `GameStarted`, `RoleAssigned` (×N), and `PhaseChanged` events —
all implement `ShouldBroadcast`. With `BROADCAST_CONNECTION=reverb` and Reverb **not running**,
the Pusher PHP SDK throws `cURL error 7: Failed to connect to localhost port 8080`.

**Exception propagation chain:**
```
BroadcastEvent::handle() → NO try-catch → exception propagates
→ SyncQueue::executeJob() → caught → handleException() → throw $e (re-throws)
→ Dispatcher::broadcastEvent() → Dispatcher::dispatch() → event() helper
→ RoleAssignmentService::assign() → DB::transaction() ROLLBACK
→ GameEngine::startGame() → outer DB::transaction() ROLLBACK
→ NarratorLobby::startGame() → HTTP 500
```

**Fix**: Started Reverb (`php artisan reverb:start`). Broadcasts now succeed →
no exception → transaction commits → 200 OK.

**Files involved**: `GameEngine.php:25`, `RoleAssignmentService.php:77-80`, `PhaseManager.php:36`

### Issue 2: Player list not updating when new players join

**Root cause (first level)**: Reverb not running → `PlayerJoined` broadcast never reaches
Echo listeners → `PlayerList` never refreshes.

**Root cause (second level, found during testing)**: Even with `wire:poll.3s` added,
`PlayerList::render()` never calls `$this->refreshPlayers()`. When polling fires,
Livewire re-renders the stale `$this->players` array from `mount()`.

**Fix**: Added `$this->refreshPlayers()` call in `PlayerList::render()` at `PlayerList.php:39`.
Now every poll cycle re-queries the DB for fresh player data.

### Issue 3: Start Game button stays disabled after polling

**Root cause**: `NarratorLobby::render()` called `$this->refreshPlayerCount()` (updates the
count) but never called `$this->validateConfig()`. When a new player joined, the counter
updated but `$canStart` remained `false` because validation didn't recalculate.

**Fix**: Added `$this->validateConfig()` call in `NarratorLobby::render()` at `NarratorLobby.php:178`.
Now every poll cycle re-validates the config (count check, role constraints, etc.) and
enables/disables the start button accordingly.

### Issue 4: Ngrok multi-tunnel instability

**When**: Tried running two ngrok tunnels (8000 + 8080) in a single config file.

**Problem**: Ngrok free plan only supports 1 tunnel per agent. Both tunnels claimed the
same URL → connection instability ("The underlying connection was closed").

**Fix**: Reverted to single-tunnel config (Laravel only, port 8000). Second ngrok process
is killed. Ngrok config at `ngrok.yml` now has only the `laravel` tunnel.

---

## Session May 29 — Changes Made (File-by-File)

### `app/Livewire/Shared/PlayerList.php`
```php
public function render()
{
    $this->refreshPlayers();  // ← ADDED — refreshes data on every render (polling or not)
    return view('livewire.shared.player-list');
}
```

### `app/Livewire/Narrator/NarratorLobby.php`
```php
public function render()
{
    $roles = Role::orderBy('faction')->orderBy('key')->get()->groupBy('faction');

    $this->refreshPlayerCount();
    $this->validateConfig();  // ← ADDED — recalculates canStart and validationErrors

    return view('livewire.narrator.narrator-lobby', [ ... ]);
}
```

### `resources/views/livewire/shared/player-list.blade.php`
```blade
<div wire:poll.3s>  ← ADDED
```

### `resources/views/livewire/narrator/narrator-lobby.blade.php`
```blade
<div wire:poll.3s>  ← ADDED
```

### `C:\Users\admin\AppData\Local\ngrok\ngrok.yml`
```yaml
# From: two tunnels (laravel + reverb) — unstable on free plan
# To: single tunnel (laravel only)
version: "3"
agent:
  authtoken: 3DR5LdrG91Ccfza6uhityNhKIip_3SWrSYzySCgZ8mQskm4qo
tunnels:
  laravel:
    proto: http
    addr: 8000
```

### Previous Sessions — Changes Already in Place
- `bootstrap/app.php`: Added `trustProxies(*)`, middleware aliases, channels registration
- `resources/views/layouts/app.blade.php`: Added CSRF meta tag (`<meta name="csrf-token">`)
- `resources/js/bootstrap.js`: Added Echo config with `authEndpoint` + `csrfToken`
- `.env`: Removed `URL::forceScheme('https')`, set `ASSET_URL=`, uncommented `APP_URL`
- `config/livewire.php`: Commented out `asset_url`

---

## What Still Needs Work (Detailed)

### HIGH PRIORITY — Needed for a working playtest

| Task | File(s) | What to build | Spec reference |
|---|---|---|---|
| **NarratorDashboard** | `app/Livewire/Narrator/NarratorDashboard.php` + blade | Phase advancement buttons (Start Night, Start Day, Start Voting, Resolve Night), player role list, Little Girl Caught button, disconnect indicators | AGENTS.md "Narrator Capabilities" section |
| **VotingService::resolve()** | `app/Game/Services/VotingService.php` | Full vote resolution: count votes, detect ties, handle Scapegoat override, Village Idiot survival, Stuttering Judge second vote, Devoted Servant swap | AGENTS.md "Vote Ownership" + "Edge Case Decisions" |
| **Shared werewolf kill panel** | `app/Livewire/Werewolves/WerewolfKillPanel.php` (new) | Real-time shared target selection visible to all werewolves via `werewolves.{room_id}` channel, Confirm Kill button enabled only when all agree | AGENTS.md "Werewolf Kill Mechanism" |
| **Witch save/poison** | `app/Livewire/Player/WitchPanel.php` (new) | Show werewolf kill target, optional save (one-time), optional poison (one-time), save evaluated before poison in resolver | AGENTS.md "Witch save and poison ordering" |
| **Seer/Fox results** | Events + Echo listeners | Private broadcast of inspect/sniff results to `player.{id}` channel | AGENTS.md Rule 5 |
| **Death chains** | `GameEngine.php`, `PlayerEliminated` handler | Lover death → partner dies → Hunter fires (if dying hunter) → Knight infection | AGENTS.md Rule 9 |
| **VillageIdiotRevealed event** | When Village Idiot is voted out | Set `voting_banned=true`, publicly reveal role, do NOT fire `PlayerEliminated` | AGENTS.md "Village Idiot survival" |
| **Little Girl Caught button** | NarratorDashboard | Dedicated button visible only when Little Girl is alive AND phase=night AND step=werewolf wake. Bodyguard does NOT block. | AGENTS.md "Little Girl Caught" |
| **Disconnect handling** | Game Engine or middleware | 2-min reconnect window, silent death (no chain effects), narrator indicator | AGENTS.md "Disconnect Mid-Game" |

### MEDIUM PRIORITY

| Task | File(s) | Notes |
|---|---|---|
| Bear Tamer growl | Adjacency via `seat_order` in `game_states.data` | Announce on role card, no growl if Bear Tamer dead |
| Pied Piper enchant | Broadcast to enchanted players, run win check after each enchant | Can win mid-night |
| White Werewolf solo kill | Track `white_werewolf_solo_night` counter, available every other night starting N2 | Optional — can pass |
| Accursed Wolf-Father convert | Mutually exclusive with werewolf group kill | `wolf_father_used` flag in data |
| Wolf Hound choose side | Night 1 only, added to werewolf channel only if chooses werewolf | Fox/WW detection rules |
| Elder vote-out disables abilities | `elder_abilities_disabled = true`, every village role must check this flag | Applies even if Elder survived first attack but is voted out later |

### LOWER PRIORITY

| Task | Notes |
|---|---|
| Scapegoat decree | Must be collected before player is eliminated |
| Devoted Servant swap | Must be offered before role reveal, window closes after public reveal |
| Stuttering Judge second vote | Triggers new full vote (separate from tiebreak), one-time use |
| Knight with Rusty Sword | Infection at next night's Step 1, cancelled if infected werewolf voted out before |
| Fox loses ability on wrong sniff | Permanent — `fox_ability_active = false`, never recovers |
| Two Sisters / Three Brothers | Need exactly 2 (Sisters) or 3 (Brothers) players, night communication channel? |
| New Game after Game Over | Reuse same room, reset state, keep players. No new room created. |
| Night decoy system | Puzzles for non-acting players (math, riddles, etc.). Stateless, client-side only. |

### INFRASTRUCTURE

| Task | Notes |
|---|---|
| **Ngrok paid plan upgrade** | Only needed if real-time WebSocket via remote access is required. Paid plan gives 3 tunnels with separate URLs. |
| **Reverb TLS** | Needed for `wss://` through Ngrok. If paid plan, configure TLS on Reverb or let Ngrok handle it. |
| **Livewire polling perf** | `wire:poll.3s` works but creates 1 request per component every 3s. For 2 components (NarratorLobby + PlayerList) = ~40 requests/min. Acceptable for MVP. Can be optimized later. |

---

## Known Bugs & Limitations

### Bug 1: Multi-tunnel Ngrok broken on free plan
- **Symptom**: Connections fail with "The underlying connection was closed"
- **Cause**: Free plan supports 1 tunnel per agent. Two tunnels in config share the same URL.
- **Status**: Reverted to single tunnel. No fix planned — upgrade to paid plan or live with polling.

### Bug 2: Echo won't connect from Ngrok HTTPS page
- **Symptom**: WebSocket connection errors in browser console (`wss://...` fails)
- **Cause**: Ngrok single tunnel goes to Laravel (port 8000), not Reverb (port 8080).
  Echo connects to `ws://192.168.100.142:8080` (LAN IP) but browser blocks `ws://` from HTTPS page.
- **Workaround**: `wire:poll.3s` handles updates. Echo listeners remain for LAN users accessing via HTTP.

### Bug 3: Nested Livewire component polling
- **Note**: `PlayerList` is a nested component inside `NarratorLobby`. Both have `wire:poll.3s`.
  This creates 2 separate polling requests every 3 seconds. No conflict — Livewire handles
  this correctly — but it's 2× the network requests.

---

## How to Start the App

```
Terminal 1: php artisan serve --host=0.0.0.0 --port=8000
Terminal 2: php artisan reverb:start
Terminal 3: ngrok start laravel           (uses C:\Users\admin\AppData\Local\ngrok\ngrok.yml)
```

### If Ngrok config is wrong or lost:
```yaml
# C:\Users\admin\AppData\Local\ngrok\ngrok.yml
version: "3"
agent:
  authtoken: 3DR5LdrG91Ccfza6uhityNhKIip_3SWrSYzySCgZ8mQskm4qo
tunnels:
  laravel:
    proto: http
    addr: 8000
```

### Environment file: `.env` key lines
```
APP_URL=https://crunchy-junkie-army.ngrok-free.dev
NGROK_URL=https://crunchy-junkie-army.ngrok-free.dev
ASSET_URL=

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=sync

REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_HOST=192.168.100.142
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

---

## Next Session: Where to Start

### Immediate next step: Test the two fixes
1. Open the Ngrok URL in a browser
2. Create a room (narrator view)
3. Join players from other devices
4. Verify player list updates every ~3s (polling)
5. Configure 4 roles (e.g., 3 villagers + 1 werewolf)
6. Verify `canStart` enables and button is clickable
7. Click Start Game — should now work (Reverb is running)

### If Start Game still fails:
- Check Reverb is running: `Get-Process | Where-Object {$_.CommandLine -like '*reverb*'}`
- Check Laravel log: `Get-Content storage/logs/laravel.log -Tail 50`
- Check if the broadcast exception still occurs: search log for "Pusher error" or "cURL"

### After lobby flow works, build in this order:
1. **NarratorDashboard** — phase controls so narrator can advance the game
2. **Shared werewolf kill panel** — first night action to test
3. **ActionResolver flow** — ensure night resolution processes correctly
4. **Seer result broadcast** — first private result to test
5. **Voting system** — resolve votes with all edge cases
6. **Witch save/poison**
7. **Death chains** — critical for game correctness
8. **All other night actions** — Fox, Pied Piper, White Werewolf, etc.
