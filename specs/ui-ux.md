# specs/ui-ux.md — UI & UX Specification

## Core UX Goals

- Minimal taps — never more than 2 taps to complete a night action
- Discussion happens away from screens — app is a support tool, not the focus
- Readability optimized for dark rooms
- Transitions feel cinematic
- Dark atmospheric design throughout
- Low cognitive load — players should never be confused about what to do

---

## Visual Direction

### Theme
- Medieval village at night
- Moonlight, fog, candles, shadows
- Dark backgrounds with warm amber/gold accents
- Muted palette — no bright or saturated colors except for emphasis

### Typography
- Serif or gothic-adjacent for titles and role names
- Clean sans-serif for UI elements and instructions
- Large readable font sizes — dark room legibility is priority

### Avoid
- Flashy arcade visuals
- Bright whites or neons
- Cluttered interfaces
- Childish UI patterns
- Heavy drop shadows or material design aesthetics

---

## Language

All UI text is bilingual FR/EN. Language is set per room and applied to all connected
clients. Players cannot override the room language individually (MVP).

---

## Screen Map

```
App Entry
 ├── Home Screen
 │    ├── Create Room → Narrator Lobby
 │    └── Join Room → Player Lobby
 │
 ├── Narrator Lobby
 │    ├── QR Code display
 │    ├── Player list
 │    ├── Role configuration panel
 │    └── Start Game → Narrator Dashboard
 │
 ├── Player Lobby
 │    └── Waiting screen → Player Game Screen
 │
 ├── Narrator Dashboard
 │    ├── Player panel (roles, alive/dead)
 │    ├── Live action feed
 │    ├── Phase controls
 │    └── Game log
 │
 └── Player Game Screen
      ├── Role Card (masked by default)
      ├── Night Action Panel (shown when woken)
      ├── Voting Panel (shown during voting phase)
      └── Results / Game Over Screen
```

---

## Mask / Unmask System

### Principle
Players hold their phone face-down or screen-dark between interactions.
When they need to check their role or action — hold to reveal, release to hide.

### Behavior
| State | Appearance |
|---|---|
| Masked (default) | Solid black card face |
| Revealed (holding) | Full content visible |
| Released | Returns to black immediately |

### Maskable Elements
1. **Role card** — role name, faction, ability description
2. **Submitted night action** — action type and target name
3. **Received results** — Seer inspect result, Fox result, enchant notification, lover identity

### Implementation
- Livewire component with `wire:mousedown` / `wire:mouseup` and `wire:touchstart` / `wire:touchend`
- State: `$revealed = false` — toggled on hold/release
- No tap-to-toggle — hold is intentional (prevents accidental reveal)
- No auto-hide timer — release is the only hide trigger

---

## Home Screen

Clean, atmospheric. Two options only:

- "Créer une partie" / "Create a game" → triggers room creation flow
- "Rejoindre une partie" / "Join a game" → shows code input + camera for QR scan

Background: dark village illustration or subtle animated fog.

---

## Narrator Lobby Screen

### Layout
- Top: Room code (large, bold) + QR code side by side
- Middle: Live player list (nicknames, joined indicator)
- Bottom: Role configuration panel + Start Game button

### Role Configuration Panel
- Roles grouped by faction (Village, Werewolves, Neutral)
- Each role: name, short description, +/- counter
- Running total: "12 / 12 players assigned" (green when valid, red when not)
- Inline validation errors
- Start Game button: disabled + greyed out until all checks pass

---

## Player Lobby Screen

Minimal. Player has nothing to do but wait.

- Room code displayed (small, for reference)
- "En attente du narrateur..." / "Waiting for the narrator..."
- List of connected player nicknames (no roles)
- Own nickname highlighted
- Subtle animation (candle flicker, fog drift)

---

## Narrator Dashboard Screen

### Layout (vertical, full screen)

**Header bar:**
- Current phase badge (NIGHT / DAY / VOTE)
- Round number
- Room code (small)

**Player Panel (scrollable list):**
- Per player row: nickname | role name | faction icon | alive/dead indicator
- Dead players: greyed out, struck through
- Lovers: small heart icon + partner name on hover/tap
- Enchanted: small star icon (Pied Piper)

**Live Action Feed:**
- Scrollable feed, newest at top
- Format: `[Role icon] [Nickname] → [action] → [Target nickname]`
- Color coded by action type (kill = red, inspect = blue, save = green, poison = purple)
- Timestamp per entry

**Phase Control Panel (bottom, fixed):**
- Large primary action button (e.g. "Wake Werewolves")
- Secondary buttons for parallel actions if needed
- Button labels change automatically per phase state

**Game Log (collapsible drawer):**
- Full chronological event history
- Accessible via bottom drawer handle

---

## Player Game Screen

### Role Card (default view)

Shown after game starts. Masked by default.

Masked state:
- Full screen black card
- Small text: "Maintenez pour révéler" / "Hold to reveal"
- Subtle card texture or ornament

Revealed state (holding):
- Role name (large, serif)
- Faction name + faction color
- Ability description (concise)
- Role flavour text (atmospheric, short)

### Night Action Panel

Shown only when narrator wakes this player's role.
Overlays or replaces the role card view.

Elements:
- Instruction text (role-specific)
- Target list (living players, scrollable)
- Confirm button (appears after selection)
- Confirmation prompt ("Confirmer?" / "Confirm?")
- Submitted state: masked action card (hold to reveal)

### Voting Panel

Shown during Voting phase to eligible players.

Elements:
- "Qui éliminer?" / "Who to eliminate?"
- List of living players (excluding self)
- Tap to select → confirm button appears
- Confirmation prompt
- Submitted state: "Vote envoyé" / "Vote submitted"
- Banned state (Village Idiot / last decree): "Vous ne pouvez pas voter ce tour" / "You cannot vote this round"

### Results / Notifications

Private results (Seer, Fox, enchant, lover link) appear as masked cards.

Format:
- Small card overlay with masked face
- "Hold to reveal" instruction
- On reveal: result content

### Game Over Screen

Shown to all players simultaneously when GameFinished fires.

Elements:
- Winning faction name (large)
- Winning faction description / flavor text
- All player roles revealed (list: nickname → role)
- Round count and game duration
- "Nouvelle partie" / "New game" button (returns to home)

---

## Animations & Transitions

| Transition | Animation |
|---|---|
| Phase change | Slow dark fade |
| Role card reveal | Card flip |
| Player eliminated | Card burns / fades to grey |
| Night start | Screen dims further, moon rises |
| Day start | Screen lightens slightly, ambient shift |
| Game over | Dramatic fade, faction emblem appears |

Animations should feel cinematic, not gamey. Short and impactful.

---

## Audio Cues (Atmospheric)

Used to reinforce phase transitions. Narrator controls pacing, audio is supplemental.

| Moment | Sound |
|---|---|
| Night start | Low ambient wind, distant wolf howl |
| Day start | Subtle morning ambience |
| Vote elimination | Single bell toll |
| Game over (village wins) | Triumphant but muted chord |
| Game over (werewolves win) | Dissonant low note |

All audio optional — players can mute per device.

---

## Accessibility Considerations (MVP)

- High contrast text on dark backgrounds
- Font size minimum 16px for instructions, 20px+ for role names
- Touch targets minimum 44x44px
- Hold gesture has sufficient visual feedback (subtle glow border on hold)
