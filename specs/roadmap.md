# specs/roadmap.md — Development Roadmap

## Development Philosophy

- Build the MVP first — no premature optimization
- Stable architecture over fast features
- Each phase must be fully working before moving to the next
- Test each system in isolation before integration

---

## Phase 1 — Project Foundation

**Goal:** Working Laravel project with correct stack and DB structure.

Tasks:
- Initialize Laravel project
- Configure SQLite database
- Install and configure TailwindCSS
- Install and configure Livewire
- Install and configure Laravel Reverb
- Configure Ngrok integration (store tunnel URL in .env)
- Set up bilingual language files (FR/EN) — `lang/fr/` and `lang/en/`
- Create base Blade layout (dark atmospheric shell)
- Run initial migrations (rooms, players, roles, game_states, night_actions, votes, couple_bonds)
- Seed roles table with all 25+ role definitions
- Verify Reverb WebSocket connection works locally

**Deliverable:** `php artisan serve` runs, Reverb connects, DB migrated and seeded, base layout renders.

---

## Phase 2 — Lobby System

**Goal:** Host can create a room, players can join, narrator can configure roles.

Tasks:
- `LobbyService` — create room, generate code, validate joins
- `CreateRoom` Livewire component — narration mode selection, room creation
- `JoinRoom` Livewire component — code input + QR scan
- QR code generation (embed Ngrok URL)
- Narrator Lobby screen — player list, role configuration panel, start game button
- Player Lobby screen — waiting state, connected player list
- Real-time player join/leave via Reverb (`PlayerJoined`, `PlayerLeft`)
- Start game validation (player count, role count, constraints)
- Session token generation and cookie storage

**Deliverable:** Full lobby flow working. Multiple devices can join the same room via QR code.

---

## Phase 3 — Role System

**Goal:** Roles assigned at game start, each player sees their role card with mask/unmask.

Tasks:
- `RoleAssignmentService` — random role distribution respecting count config
- All 25+ role classes implementing `RoleInterface`
- All 6 faction classes implementing `FactionInterface`
- `RoleCard` Livewire component — masked/revealed states, hold gesture
- Private Reverb broadcast of role to `player.{player_id}` on game start
- Narrator dashboard shows all roles
- Role card flip animation on game start

**Deliverable:** Game starts, each player privately sees their role, narrator sees all roles.

---

## Phase 4 — Game Engine & Narrator Dashboard

**Goal:** Narrator can drive the game through all phases. Engine transitions state correctly.

Tasks:
- `GameEngine`, `PhaseManager`, `WinConditionChecker` classes
- All Phase classes (`NightPhase`, `DayPhase`, `VotingPhase`, `FinishedPhase`)
- `NarratorDashboard` Livewire component — player panel, phase controls, game log
- `PhaseControls` Livewire component — contextual buttons per phase
- Phase transition broadcasts via Reverb (`PhaseChanged`)
- Round counter logic
- Player screens react to phase changes (show/hide panels)
- Narrator narration prompt cards per phase step
- Game log (chronological event list on dashboard)

**Deliverable:** Narrator can drive a full game loop (Night → Day → Vote → Night) with correct state transitions and real-time updates on all devices.

---

## Phase 5 — Action System

**Goal:** All night actions work correctly for all 25+ roles.

Tasks:
- `ActionInterface`, `BaseAction`, `NightAction` classes
- `ActionService` — submission, validation, storage
- `ActionResolver` — deferred batch resolution with full priority order
- `NightAction` Livewire component — per-role action panels, mask/unmask
- Werewolf group panel (shared kill target)
- Witch sequential prompts (kill reveal → save → poison)
- Cupid panel (link 2 players) + `couple_bonds` record creation
- Lovers notification (private broadcast on link)
- All per-role special logic:
  - Knight with Rusty Sword delayed death
  - Wolf Hound choose-side
  - Bear Tamer growl (narrator dashboard indicator)
  - Fox sniff result + ability loss
  - Pied Piper enchant tracking
  - Accursed Wolf-Father conversion
  - White Werewolf solo kill cadence
  - Elder ability disable chain
  - Devoted Servant swap window
- Death chain resolution (Lovers, Hunter, Knight infection)
- Private result broadcasts (Seer, Fox, enchant notification, conversion)
- `NightResolved` broadcast to all devices

**Deliverable:** All roles' night actions submit, resolve correctly in batch at dawn, results delivered privately, deaths applied with correct chains.

---

## Phase 6 — Voting System

**Goal:** Full voting flow works with all edge cases.

Tasks:
- `VotingService` — vote collection, result calculation, tie handling
- `VotingPanel` Livewire component — player voting UI, confirmation, submitted state
- Narrator vote count display (live feed, per-candidate counts)
- Tie handling — Scapegoat override, configurable default
- Village Idiot survival flow + vote ban
- Elder first-vote survival + ability disable
- Stuttering Judge second vote signal + trigger
- Devoted Servant pre-reveal swap window during vote elimination
- Death chain on vote elimination (Lovers, Hunter, Angel round 1)
- `WinConditionChecker` runs after every elimination
- `GameFinished` event + game over screen for all devices

**Deliverable:** Complete voting flow works including all special cases. Win conditions detected and displayed correctly.

---

## Phase 7 — UX Polish

**Goal:** The app feels immersive, atmospheric, and production-quality.

Tasks:
- Full dark atmospheric visual design (color palette, typography, iconography)
- Card flip animation on role reveal
- Phase transition animations (dark fade, night/day shifts)
- Player elimination animation (fade to ash)
- Live feed slide-in animations
- Game over screen (faction emblem, player role reveal list)
- Audio cues implementation (night start, day start, elimination bell, game over)
- Background atmospheric element (fog, village silhouette, or embers)
- Mute toggle (persisted per device)
- Dark room readability audit (contrast, font sizes, touch targets)
- Bilingual text audit — all strings in lang files, none hardcoded
- Mobile viewport and touch optimization
- PWA manifest (optional — allows "Add to Home Screen")

**Deliverable:** Full immersive experience. The app looks and feels like a Loup-Garou companion, not a generic web app.

---

## Future Phases (Post-MVP)

### App Narrator Mode
- Automatic timers per phase
- Auto role wake/sleep sequencing
- Automatic narration text display
- No narrator device required

### Advanced Features
- Narrator can remove players mid-game
- Game pause / resume
- Reconnection with host migration
- Narrator preset configurations (save/load role setups)

### Cloud Hosting
- Deploy to Laravel Forge or Vapor
- Persistent rooms
- Internet play without physical co-location
- Multiple concurrent rooms

### Extended Content
- Custom role creator (narrator defines abilities)
- Role expansion packs
- Custom factions

### Analytics & History
- Game replay system
- Session statistics (win rates per faction, average game length)
- Player history (local, no account required)

### Platform
- Native mobile wrapper (Capacitor or similar)
- Offline-capable PWA
- Spectator mode
