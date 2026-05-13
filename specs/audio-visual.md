# specs/audio-visual.md — Audio & Visual Specification

## Design Philosophy

Every visual and audio element serves one purpose: immersion.

The app should feel like a dark medieval village at night.
Players should forget they are looking at a web app.
Audio and visuals support the narrator's atmosphere — they do not compete with it.

---

## Visual Language

### Color Palette

| Role | Color | Usage |
|---|---|---|
| Background (primary) | `#0D0D0D` — near black | Screen backgrounds |
| Background (surface) | `#1A1510` — dark warm brown | Cards, panels |
| Background (elevated) | `#251E16` — medium warm brown | Modal overlays, dashboards |
| Text (primary) | `#E8D9B5` — parchment | Body text, instructions |
| Text (secondary) | `#9A8A6A` — aged gold | Labels, secondary info |
| Accent (warm) | `#C8922A` — amber | Highlights, active states, role names |
| Accent (danger) | `#8B2020` — blood red | Kill actions, eliminations, werewolf faction |
| Accent (village) | `#3A6B3A` — forest green | Village faction |
| Accent (neutral) | `#5A5A8A` — slate blue | Neutral / solo factions |
| Accent (lovers) | `#8B4A6B` — deep rose | Lovers bond |
| Masked card | `#000000` — pure black | Masked state |
| Dead player | `#3A3530` — ash | Dead player rows |

### Typography

| Element | Style |
|---|---|
| Role names, titles | Serif or gothic — e.g. `Cinzel`, `MedievalSharp` |
| UI elements, instructions | Clean sans-serif — e.g. `Inter`, `DM Sans` |
| Narration prompts | Italic serif — atmospheric, readable |
| Phase badges | Bold uppercase sans-serif |

Font sizes:
- Role name on card: 28–32px
- Instructions: 16–18px
- Player list: 16px
- Phase badge: 14px bold uppercase
- Narrator live feed: 14px

### Iconography

Simple, line-based icons. No clipart or cartoon style.

| Faction / State | Icon style |
|---|---|
| Werewolf faction | Wolf silhouette |
| Village faction | Candle or house |
| Seer | Eye |
| Witch | Potion bottle |
| Hunter | Crossbow |
| Lovers | Simple heart |
| Pied Piper | Flute |
| Dead | Skull or X |
| Alive | Candle flame |
| Enchanted | Star |

---

## Card Design

### Role Card (Player)

```
┌─────────────────────────┐
│                         │
│     [ROLE ICON]         │
│                         │
│   LOUP-GAROU            │  ← role name (serif, large)
│   Faction: Loups        │  ← faction (small, colored)
│                         │
│   Chaque nuit, les      │  ← ability description
│   loups choisissent     │
│   une victime.          │
│                         │
│   "La forêt vous        │  ← flavor text (italic)
│   appartient..."        │
└─────────────────────────┘
```

Masked state: pure black face, subtle card texture, "Maintenez pour révéler" at bottom.

### Night Action Card (masked)

```
┌─────────────────────────┐
│                         │
│   [ACTION ICON]         │
│                         │
│   Action soumise        │  ← "Action submitted"
│                         │
│   Maintenez pour        │  ← "Hold to reveal"
│   révéler               │
│                         │
└─────────────────────────┘
```

Revealed: shows action type + target name.

### Result Card (Seer, Fox, etc.)

Same masked card format. Revealed shows result content.

---

## Animations

All animations are CSS transitions or subtle JS-driven effects.
No heavy animation libraries. Performance is critical on low-end mobile devices.

| Element | Animation | Duration | Easing |
|---|---|---|---|
| Card reveal (hold) | Fade in content | 200ms | ease-in |
| Card mask (release) | Fade to black | 150ms | ease-out |
| Phase transition | Full screen dark fade | 600ms | ease-in-out |
| Night start | Screen darkens + subtle vignette deepens | 800ms | ease-in |
| Day start | Slight brightness increase | 800ms | ease-out |
| Player eliminated | Row fades to ash, light strike-through | 500ms | ease-in |
| New action in feed | Slide in from top | 200ms | ease-out |
| Game over reveal | Faction emblem fades in, slow pulse | 1200ms | ease-in-out |
| QR code appear | Fade in | 300ms | ease-out |

### Card Flip (Role Reveal at Game Start)

On game start, each player's role card does a single slow flip from blank to revealed,
then immediately returns to masked state.
This creates a theatrical "check your role" moment before the first night.

Duration: 800ms flip, 2 second hold, 400ms flip back to masked.

---

## Audio Design

### Principles

- Subtle and atmospheric — should not distract from conversation
- Short cues only — no looping music that overpowers the narrator
- All audio optional — mute button always accessible
- Spatial audio not required — mono/stereo both fine

### Audio Cues

| Trigger | Sound | Duration | Notes |
|---|---|---|---|
| Night start | Low ambient wind, distant wolf howl | 3–4s | Played once on phase change |
| Day start | Soft morning ambience, distant birds | 2–3s | Played once |
| Player joins lobby | Soft door creak | 0.5s | |
| Action submitted (player) | Soft parchment rustle | 0.3s | Private — only submitting device |
| Vote submitted | Faint coin drop or seal stamp | 0.3s | Private |
| Voting closed | Single quiet bell | 1s | All devices |
| Player eliminated | Low single bell toll | 2s | All devices |
| Game over — village wins | Muted triumphant choir chord | 3s | |
| Game over — werewolves win | Deep dissonant bass note | 3s | |
| Game over — lovers win | Melancholic string chord | 3s | |
| Game over — solo faction | Ethereal single note | 3s | |
| Bear Tamer growl | Low animal growl | 1.5s | Narrator device only |

### Audio Implementation

- Audio files: `.mp3` format, compressed, max 100KB each
- Playback via Web Audio API or simple `<audio>` element
- Triggered by Livewire events or JS listeners on Reverb events
- Volume: default 40%, user adjustable
- Mute: persisted in localStorage per device

---

## Atmospheric Background

The app background is not plain black — it has subtle depth.

Options (implement one for MVP):
- Static dark illustration: village silhouette under moonlight
- Subtle CSS animated fog: slow-moving translucent layers
- Particle effect: slow floating embers or fireflies (very subtle, low performance cost)

The background should be barely noticeable — present when the player glances at the screen
but not distracting during conversation.

---

## Dark Room Optimization

All visual decisions are made for readability in a dark room:

- No pure white text (use parchment `#E8D9B5`)
- No bright backgrounds
- Sufficient contrast ratio: minimum 4.5:1 for all text
- Large touch targets (44x44px minimum)
- High contrast active states (amber glow on selected item)
- Phase badge always visible with colored background
