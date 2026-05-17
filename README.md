# Loup-Garou Companion

> *Under a crescent moon, the village gathers. Not everyone will see the dawn.*

A real-time social deduction companion app for **Les Loups-Garous de Thiercelieux** (Werewolf).
Players are physically together in the same room. The app manages hidden information — roles,
night actions, voting, and game state — while keeping human conversation at the center of
the experience. No online matchmaking, no strangers, no screens dominating the table.
Just the glow of a phone, the flicker of a candle, and the weight of suspicion.

---

## Features

### Lobby
- **Room creation** — Narrator creates a room, gets a 6-character code and QR code
- **Join via QR or code** — Players scan the QR or type the room code in their browser
- **Live player list** — See who has joined in real time
- **Role configuration** — Narrator assigns role counts per faction; validation enforces structural rules (Two Sisters = 2, Three Brothers = 3, solo factions ≤ 1)
- **Start validation** — Checks minimum players, role count match, faction presence

### Roles
- **25 unique roles** across 3 factions (Village, Werewolves, Neutral)
- **Every role has an implementation class** with its specific ability, night order, and win condition
- **Lovers bond** created by Cupid on night 1, tracked via `couple_bonds` table
- **Mask/unmask cards** — Hold to reveal your role, release to hide; prevents accidental exposure

### Game Engine
- **Phase management** — `PhaseManager` is the single class that transitions the game state
- **5 phases** — Waiting, Night, Day, Voting, Finished
- **Round counter** — Increments each night cycle
- **6 win conditions** — Angel, White Werewolf, Pied Piper, Werewolves, Village, Lovers (checked in priority order)
- **WinConditionChecker** runs after every single elimination — no missed victory

### Night Actions
- **Deferred resolution** — Actions are stored when submitted, resolved as a batch at dawn
- **11 priority levels** — From Bodyguard protection (earliest) to Seer inspection (latest)
- **Death chains** — Lover death triggers partner death; Hunter fires before following; Knight infection applies at next night start
- **Private results** — Seer, Fox, and enchantment results broadcast only to each player's private channel

### Voting
- **Anonymous voting** — Narrator sees tallies but never who voted for whom
- **Tie resolution** — Scapegoat is eliminated (if alive); otherwise, no elimination, no random fallback
- **Village Idiot** — Survives the vote, permanently banned from voting, role revealed publicly
- **Elder vote-out** — Disables all village abilities for the rest of the game
- **Stuttering Judge** — May trigger a second full vote once per game
- **Devoted Servant** — May swap identities with the condemned before their role is revealed

### Narrator Dashboard
- **Full player visibility** — All roles, faction, alive/dead, lover bonds, enchanted status
- **Live action feed** — Real-time incoming night actions during the night phase, color-coded by type
- **Phase controls** — Context-sensitive buttons (Start Night, Resolve Night, Start Voting, End Game)
- **Game log** — Scrollable timeline of every event: phase changes, eliminations, votes, suspicious access attempts
- **Suspicious access alerts** — Any security violation is logged and broadcast to the narrator dashboard

### Security
- **Session token identity** — No accounts, no passwords; a UUID cookie identifies each player
- **Access control** — Every request verifies room ownership, role, alive status, and narrator flag
- **Silent redirect + narrator alert** — Violations return `403` and fire a `SuspiciousAccessAttempt` event to the narrator
- **Channel authorization** — Every WebSocket channel has an explicit auth rule in `routes/channels.php`

### Localisation
- **Full bilingual support** — English and French, all strings go through Laravel `lang/` files
- **4 lang file groups** — `ui.php`, `roles.php`, `narration.php`, `game.php`, `decoys.php`, `lobby.php`
- **Locale toggle** — EN/FR switch on the home screen; room locale is fixed after creation

---

## Tech Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | **Laravel 13** | PHP framework — routing, Eloquent ORM, event system, queues |
| Templating | **Blade** | Server-rendered HTML with Livewire integration |
| Reactive UI | **Livewire 4** | Full-stack reactivity without writing JavaScript |
| CSS | **TailwindCSS 4** | Utility-first CSS with custom dark theme palette |
| Real-time | **Laravel Reverb** | WebSocket server — broadcasts game events to all devices |
| Database | **SQLite** | Zero-config, file-based — no database server needed for MVP |
| Tunnel | **Ngrok** | Public HTTPS URL for the local dev server — players join from anywhere |
| Fonts | **Cinzel** + **Inter** | Serif display font for titles; clean sans-serif for body text |
| QR | **chillerlan/php-qrcode** | SVG QR code generation (no GD extension required) |

Laravel was chosen for its mature ecosystem, built-in broadcasting with Reverb, and Blade/Livewire
pair that produces reactive UIs without a separate JavaScript framework. TailwindCSS keeps the
atmospheric dark theme consistent without writing custom CSS. SQLite avoids the operational
complexity of MySQL for a local-only MVP. Ngrok provides secure HTTPS tunnels so players on
any network can join — no WiFi hotspot or LAN configuration required.

---

## Architecture Overview

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
 │    └── Services/
 │         ├── LobbyService.php
 │         ├── RoleAssignmentService.php
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
      ├── Controllers/            ← thin — no business logic
      └── Livewire/               ← reactive UI components
```

### Layer Responsibilities

**Game Engine** — Top-level orchestrator. Starts the game, advances phases, calls `ActionResolver`
at dawn, calls `VotingService::resolve` after voting, triggers `WinConditionChecker` after every
death. Never contains role-specific logic.

**PhaseManager** — The **only** class that writes `game_states.phase`. Validates legal transitions
(waiting→night, night→day, day→voting, voting→night/finished). All other classes call
`PhaseManager::transition()` — they never set the phase directly.

**ActionResolver** — Processes all unresolved night actions at dawn in strict priority order.
Handles death chains (Lover follows, Hunter fires, Knight infection). Runs `WinConditionChecker`
after every elimination within the chain. Never touches actions outside its dawn batch.

**WinConditionChecker** — Checks 6 factions in priority order: Angel → White Werewolf → Pied Piper →
Werewolves → Village → Lovers. Runs after **every** single elimination and after every vote
resolution. Returns the winning faction or `null`.

**Services** — Thin business-logic classes called by controllers and Livewire components.
`LobbyService` handles room creation and joining. `ActionService` validates and stores night actions.
`VotingService` manages vote submission, tally, and resolution. `RoleAssignmentService` distributes
roles at game start.

**Events** — Every domain state change fires a Laravel event that broadcasts itself via `ShouldBroadcast`.
No controller or service calls `Broadcast::` directly. Sensitive data (roles, results) only goes to
`player.{player_id}` channels. Public events (phase changes, eliminations) go to `room.{room_id}`.

### Channel Strategy

| Channel | Subscribers | Carries |
|---|---|---|
| `room.{room_id}` | All players + narrator | Phase changes, eliminations, public announcements |
| `narrator.{room_id}` | Narrator only | Live action feed, vote submissions, suspicious alerts |
| `player.{player_id}` | That player only | Role assignment, night action results, private notifications |
| `werewolves.{room_id}` | Werewolf-faction players | Shared kill target selections, identity reveal |

All channels are private. Channel authorization is in `routes/channels.php`. Roles and private
results **never** travel on the shared `room.{room_id}` channel.

---

## Prerequisites

- **PHP 8.2+** (with extensions: `pdo_sqlite`, `mbstring`, `bcmath`, `xml`, `curl`, `gd`
  or `imagick` — though QR generation uses SVG and does not require GD)
- **Composer 2.x**
- **Node.js 18+** and **npm**
- **Ngrok account** ([ngrok.com](https://ngrok.com)) and ngrok CLI installed
- **SQLite** (PHP includes support out of the box)

---

## Installation

```bash
# 1. Clone the repository
git clone <repository-url> loup-garou-companion
cd loup-garou-companion

# 2. Install PHP dependencies
composer install

# 3. Install and build frontend assets
npm install
npm run build

# 4. Configure environment
copy .env.example .env
# Edit .env — see required variables below

# 5. Generate application key
php artisan key:generate

# 6. Run database migrations and seed roles
php artisan migrate --seed

# 7. Start the Reverb WebSocket server (keep this terminal open)
php artisan reverb:start

# 8. Start Laravel development server (new terminal)
php artisan serve --host=0.0.0.0 --port=8000

# 9. Expose via Ngrok (new terminal — do not restart mid-session)
ngrok http 8000
```

### Required Environment Variables

```env
APP_NAME="Loup-Garou Companion"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Ngrok URL — copy from ngrok output after tunnel starts
# This is the URL players use to join the game
NGROK_URL=https://your-ngrok-subdomain.ngrok-free.app

# Database — SQLite is the default for MVP
DB_CONNECTION=sqlite
# For SQLite, no DB_HOST/DB_PORT/DATABASE needed — uses database/database.sqlite

# Reverb WebSocket server configuration
REVERB_APP_ID=wolf-app
REVERB_APP_KEY=wolf-key
REVERB_APP_SECRET=wolf-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Broadcasting driver must be set to reverb
BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb
```

### How Players Connect

Players do **not** need to be on the same WiFi network. Any device with internet access and the
Ngrok URL can join. The host shares the QR code (shown in the narrator lobby) or the 6-character
room code. Players open their mobile browser, type the Ngrok URL, and enter the room code on
the join screen.

No app install is required. The entire experience runs in the browser.

---

## How to Play

### Narrator Setup

1. Open the Ngrok URL on your device (laptop or tablet)
2. Tap **Create a Game** — enter your nickname, you become the narrator
3. Share the QR code or room code with players
4. Wait for players to join — their nicknames appear in real time
5. Configure role counts using the +/- controls (roles grouped by faction)
6. Tap **Start Game** when all players have joined and roles are assigned

### Game Loop

Each round follows the same rhythm:

1. **Night** — Players close their eyes. The narrator uses the dashboard to wake roles one
   by one (Werewolves → Seer → Witch → Fox → etc.). Each role sees their action panel on
   their phone. The narrator presses **Resolve Night → Day** after all roles have acted.
2. **Day** — Players open their eyes. The narrator announces the night's events (who was killed,
   Bear Tamer growl, etc.). The village discusses who they suspect.
3. **Voting** — The narrator presses **Start Voting**. Alive players select a target and confirm.
   The narrator sees the live tally. When votes are complete, the narrator resolves the vote.
   Eliminated players' roles are revealed. Death chains resolve automatically.
4. Repeat until a faction wins.

### Masks and Reveals

Every piece of private information starts hidden. Your role card, your submitted night action,
and any results you receive (Seer inspection, Fox sniff, enchantment notification) are all
displayed as a solid black card face. **Hold your finger on the card to reveal its content;
release to hide it again.** This prevents accidental exposure when your phone is face-up on
the table or when someone glances at your screen. There is no tap-to-toggle — the hold gesture
is intentional and deliberate.

### What the Narrator Can and Cannot Do

| Can do | Cannot do |
|---|---|
| See all roles and the live action feed | Override or cancel submitted actions |
| Control all phase transitions | Reveal roles to players publicly |
| Configure and start the game | Vote |
| Remove players before the game starts | Submit night actions |
| Read narration prompt cards | Be targeted by any action |

---

## Roles Reference

### Village

| Role | Faction | Night Order | Ability | Win Condition |
|---|---|---|---|---|
| Villager | Village | — | No special ability | Eliminate all werewolves |
| Seer | Village | 11 | Inspect one player each night to learn their faction | Eliminate all werewolves |
| Witch | Village | 7–8 | One save potion, one poison potion (each usable once) | Eliminate all werewolves |
| Hunter | Village | — | When eliminated, immediately eliminate one player | Eliminate all werewolves |
| Bodyguard | Village | 2 | Protect one player from werewolf attack each night | Eliminate all werewolves |
| Little Girl | Village | — | Peek at the werewolves during the night (risky) | Eliminate all werewolves |
| Cupid | Village | 1 | On night 1, link two players as lovers | Eliminate all werewolves |
| Elder | Village | — | Survives first werewolf attack; vote-out disables village abilities | Eliminate all werewolves |
| Scapegoat | Village | — | Eliminated on first tie vote; issues a last decree | Eliminate all werewolves |
| Village Idiot | Village | — | Survives vote once; permanently banned from voting | Eliminate all werewolves |
| Two Sisters | Village | — | Know each other's identity from the start | Eliminate all werewolves |
| Three Brothers | Village | — | Know each other's identity from the start | Eliminate all werewolves |
| Stuttering Judge | Village | — | Once per game, trigger a second full vote | Eliminate all werewolves |
| Knight w/ Rusty Sword | Village | — | The werewolf that kills you dies the following night | Eliminate all werewolves |
| Devoted Servant | Village | — | May swap identity with a voted-out player before their role is revealed | Eliminate all werewolves |
| Bear Tamer | Village | — | If a werewolf sits next to you, your bear growls at dawn | Eliminate all werewolves |
| Fox | Village | 10 | Sniff 3 adjacent players; if none are werewolves, ability is lost permanently | Eliminate all werewolves |

### Werewolves

| Role | Faction | Night Order | Ability | Win Condition |
|---|---|---|---|---|
| Werewolf | Werewolves | 3 | Each night, the pack selects a victim to kill | Reach parity with village |
| Big Bad Wolf | Werewolves | 4 | Extra kill each night while no werewolf has died | Reach parity with village |
| Accursed Wolf-Father | Werewolves | 5 | Once per game, convert a player instead of killing | Reach parity with village |
| White Werewolf | Werewolves* | 6 | Every other night, eliminate a werewolf | Last player standing alone |
| Wolf Hound | Werewolves | 1 (choose side) | On night 1, choose to be village or werewolf | Depends on choice |

\* White Werewolf has faction `werewolves` in the database but is treated as a solo faction
for win condition checking.

### Neutral

| Role | Faction | Night Order | Ability | Win Condition |
|---|---|---|---|---|
| Pied Piper | Neutral | 9 | Each night, enchant up to 2 players | All living players are enchanted |
| Angel | Neutral | — | Must be voted out in round 1 | Eliminated by village vote in round 1 |

---

## Night Resolution Order

Actions are resolved at dawn in this exact priority order:

| Priority | Action | Notes |
|---|---|---|
| 1 | Knight with Rusty Sword delayed death | `infected_werewolf_id` resolved immediately at night start |
| 2 | Bodyguard protection | Marks the protected player in the resolver context |
| 3 | Werewolf kill | Cancelled if target is protected, or if Wolf-Father is converting |
| 4 | Big Bad Wolf extra kill | Only available while no werewolf has died |
| 5 | Accursed Wolf-Father conversion | Mutually exclusive with the werewolf kill for this night |
| 6 | White Werewolf solo kill | Every other night (nights 2, 4, 6...); optional |
| 7 | Witch save | Cancels the werewolf kill on the same target **only** |
| 8 | Witch poison | Independent kill — cannot be cancelled; targeted player dies |
| 9 | Pied Piper enchant | Win condition checked after each enchant action resolves |
| 10 | Fox sniff | Result broadcast privately to the Fox (`player.{id}`) |
| 11 | Seer inspect | Result broadcast privately to the Seer (`player.{id}`) |

### Resolution Rules

- **Death chains** fully resolve before `WinConditionChecker` runs: Lover dies → partner dies;
  Hunter dies → fires before partner death. No mid-chain win checks.
- **Witch save** is shown the werewolf kill target before deciding. Save is evaluated before
  poison in the resolver. Both are optional and independent.
- **Bodyguard** only blocks the werewolf faction kill. It does **not** block Witch poison,
  Hunter shot, White Werewolf solo kill, or Big Bad Wolf extra kill.
- **Wolf-Father convert** replaces the werewolf kill for that night entirely. The pack does
  not kill if Wolf-Father converts.
- **Fox** loses their ability permanently if they sniff 3 non-werewolf players. Wolf Hound
  (who chose werewolf) and White Werewolf both count as werewolves for Fox detection.
- **Knight infection** — the infected werewolf dies at the start of the **next** night's
  resolution, before any other actions. If the infected werewolf is voted out before then,
  the infection is cancelled.

---

## Security and Anti-Cheat

### Identity Model

There are no user accounts. The first time a player joins a room, the app generates a UUID
(`session_token`), stores it as an `httpOnly` cookie on the device, and saves it to the
`players` table. Every subsequent request reads this cookie to identify the player.

### What Happens When a Player Violates Access Rules

The app does not silently ignore violations. It returns `403 Forbidden` and fires a
`SuspiciousAccessAttempt` event that broadcasts to the narrator dashboard alert feed.
The narrator sees the violating player's nickname and the specific details of the attempt.

This applies to:
- Accessing another player's role card URL directly
- Submitting a night action as a dead player
- A non-narrator accessing the narrator dashboard
- A narrator submitting a night action or vote
- Cross-room access (trying to view data from a room the player did not join)
- Any request with no `session_token` cookie (`401 Unauthorized`)

### Dead Player Restrictions

Dead players are blocked server-side from:
- Submitting night actions (`abort(403)` in `ActionService`)
- Submitting votes (`abort(403)` in `VotingService`)
- Being targeted by any action or vote
- Their UI shows a "You are dead" message with no interactive elements

### Channel Authorization

All four WebSocket channels are private and require explicit authorization via
`routes/channels.php`. The `room.{room_id}` channel carries only public information
(phase changes, eliminations). Roles, night action results, and other private data
are **never** broadcast on this channel — they travel exclusively on `player.{player_id}`.

---

## Localisation

All user-facing strings exist in `lang/en/` and `lang/fr/`. No text is hardcoded in
Blade templates, Livewire components, or PHP classes.

### Lang File Groups

| File | Contents |
|---|---|
| `ui.php` | Button labels, lobby strings, game state strings, vote strings, narrator dashboard strings, phase names, faction names, win announcements |
| `roles.php` | Role names and descriptions for all 25 roles (both locales) |
| `narration.php` | Narration prompts read aloud by the human narrator: phase transitions, role wake/sleep calls, elimination announcements, special event lines (Bear Tamer, Lovers, Angel, Knight, Elder) |
| `game.php` | Game metadata: phase labels, round counter, vote result messages, Hunter kill prompts, night summary |
| `decoys.php` | Night decoy content: math puzzles, riddles, unscrambles, sequences, atmospheric counts |
| `lobby.php` | Lobby-specific strings: room creation, join flow, validation errors |

### Adding a New Locale

1. Create a new directory under `lang/` (e.g., `lang/de/`)
2. Copy all 6 PHP files from `lang/en/` into the new directory
3. Translate the string values (keep the array keys identical)
4. Add the locale code to the allowed list in `AppServiceProvider::boot()` and the locale
   switch route in `routes/web.php`

### Example Lang Key Usage

```blade
{{-- Blade template --}}
<span class="text-[#C8922A]">{{ __('roles.werewolf.name') }}</span>
<p class="text-[#9A8A6A]">{{ __('ui.phase.night') }}</p>
```

```php
// PHP class
public function getName(string $locale): string {
    return __('roles.seer.name', [], $locale);
}
```

---

## Project Structure

```
app/
 ├── Game/
 │    ├── Engine/           ← GameEngine, PhaseManager, ActionResolver, WinConditionChecker
 │    ├── Roles/            ← RoleInterface + 25 role classes (Village/, Werewolves/, Neutral/)
 │    ├── Actions/          ← ActionInterface + 11 night action classes
 │    ├── Phases/           ← PhaseInterface + 5 phase classes
 │    ├── Factions/         ← FactionInterface + 6 faction classes
 │    ├── Narration/        ← Human-narrator prompt system
 │    └── Services/         ← Lobby, RoleAssignment, Voting, Action services
 ├── Events/                ← 9 broadcastable domain events
 ├── Models/                ← 7 Eloquent models
 └── Http/
      ├── Controllers/      ← Thin HTTP entry points (no logic)
      └── Livewire/         ← Reactive UI components

database/
 └── migrations/            ← 7 migration files

lang/                        ← Bilingual FR/EN (6 files per locale)
 en/ + fr/

resources/
 └── views/
      ├── layouts/          ← Base Blade layout with atmospheric layers
      ├── livewire/         ← Full set of Livewire component views
      └── errors/           ← Error page templates

routes/
 ├── web.php                ← All web routes
 └── channels.php           ← WebSocket channel authorization rules

specs/                       ← Architecture and design specifications
```

---

## Development Notes

### MVP Scope

This project is an **MVP** (Minimum Viable Product). The following are explicitly
**in scope** for the MVP:
- Lobby with QR join, role configuration, and start validation
- Human Narrator dashboard with full phase control
- All 25 roles with night actions and correct resolution order
- Voting with all edge cases (Scapegoat, Village Idiot, Elder, Stuttering Judge, Devoted Servant)
- Win conditions for all 6 factions (Angel, White Werewolf, Pied Piper, Werewolves, Village, Lovers)
- Lovers bond with death chain
- Mask/unmask system (hold to reveal)
- Night decoy system for non-acting players
- Bilingual FR/EN support
- Ngrok + SQLite local setup

### Explicitly Out of Scope

The following will **not** be built for the MVP:
- **App Narrator Mode** — automated narration with timers; deferred to post-MVP
- **Online multiplayer** — no matchmaking, no strangers playing remotely
- **AI players or AI narration**
- **Ranking, progression, or monetization** of any kind
- **User accounts or authentication** — identity is session token only
- **Cloud hosting** — local setup only for MVP
- **Cosmetics, customization, or replay system**
- **Statistics or analytics**
- **Redis** — SQLite is the single source of truth for MVP

### Key Architecture Decisions

- **No Laravel Breeze, Fortify, or standard Auth** — the app has no user accounts.
  Identity is a UUID session token stored as a cookie and in the `players` table.
- **No Vue or React** — Livewire v3/v4 handles all UI reactivity on the player and
  narrator screens.
- **No Pusher** — replaced by Laravel Reverb, the first-party WebSocket server.
- **No job queues** — all game operations happen synchronously within a single request.
  The async real-time layer is handled entirely by Reverb broadcasts.
- **Controllers are thin** — they receive the request, call one service, return a response.
  No game logic, no DB queries, no phase transitions in controllers.

---

## License

MIT License

Copyright (c) 2025

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
