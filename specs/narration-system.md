# specs/narration-system.md — Narration System Specification

## Overview

MVP supports Human Narrator Mode only.
App Narrator Mode is deferred and out of MVP scope.

The narrator is not a player. They have no role card, no vote, no night action.
Their job is to control pacing, atmosphere, and phase progression.
The app supports the narrator — it does not replace them.

---

## Human Narrator Mode

### Narrator Identity

- Created when host selects "Human Narrator" at room creation
- `is_narrator = true`, `is_host = true`, no `role_id`
- Narrator accesses a dedicated dashboard unavailable to players
- Narrator's device is the authoritative control surface for phase flow

---

## Narrator Dashboard

The dashboard is a Livewire component (`NarratorDashboard`) that updates in real time
via Reverb on the `narrator.{room_id}` private channel.

### Dashboard Sections

#### Player Panel
- Full list of all players
- Per player: nickname, role name, role faction, alive/dead status
- Dead players visually distinguished (greyed out, crossed)
- Couple bond indicator if Cupid linked two players

#### Live Action Feed
- Shows night actions as they are submitted by players
- Updates instantly via Reverb (no page refresh needed)
- Format: `[Role] → [Action] → [Target]`
- Example: `Seer → inspect → Margaux`
- Example: `Werewolves → kill → Thomas`
- Pending actions shown until resolved at dawn
- Feed is only visible to narrator

#### Phase Control Panel
- Displays current phase and round number
- Action buttons change depending on current phase (see Phase Controls below)

#### Game Log
- Chronological list of all game events
- Examples: "Round 2 — Night started", "Thomas eliminated by vote", "Seer inspected Lena"
- Narrator-only view

#### Couple Bond Indicator
- If Cupid linked two players, both are flagged on the player panel
- Shows: `❤ Lover: [partner nickname]` next to each lover

---

## Phase Controls

The narrator manually advances the game through each phase using dashboard buttons.
Buttons are contextual — only valid actions for the current phase are shown.

### Waiting Phase
- `Start Night` — transitions to Night phase, round 1

### Night Phase (per role, in night order)
- `Wake Cupid` (night 1 only)
- `Wake Lovers` (night 1 only, after Cupid acts)
- `Wake Wolf Hound` (night 1 only)
- `Wake Werewolves`
- `Wake Big Bad Wolf` (if in game and no werewolf has died)
- `Wake Accursed Wolf-Father` (if ability not yet used)
- `Wake White Werewolf` (every other night)
- `Wake Bodyguard`
- `Wake Seer`
- `Wake Witch`
- `Wake Pied Piper`
- `Wake Fox`
- `Resolve Night` — triggers ActionResolver, applies all pending actions, announces results

### Day Phase
- `Start Discussion` — begins the day discussion (no timer in human mode)
- `Start Voting` — transitions to Voting phase

### Voting Phase
- `Close Voting` — ends vote submission, calculates result
- `Announce Elimination` — narrator announces who is eliminated
- `Trigger Second Vote` (only if Stuttering Judge signaled)
- `Start Night` — begins next round

### Finished Phase
- Displayed automatically when WinConditionChecker fires GameFinished event

---

## Night Announcement Script (FR/EN)

The app provides optional narration text the human narrator can read aloud.
These are suggestion strings — the narrator is free to improvise.

Stored in `lang/fr/narration.php` and `lang/en/narration.php`.

Examples:
```
narration.night.start         = "Le village s'endort..." / "The village falls asleep..."
narration.werewolves.wake     = "Les loups-garous se réveillent..." / "The werewolves open their eyes..."
narration.werewolves.sleep    = "Les loups-garous se rendorment." / "The werewolves go back to sleep."
narration.seer.wake           = "La voyante se réveille..." / "The seer opens her eyes..."
narration.day.start           = "Le village se réveille." / "The village wakes up."
narration.elimination.vote    = "{name} est éliminé(e)." / "{name} has been eliminated."
narration.bear.growl          = "Grrrr..." / "Grrrr..."
narration.bear.silence        = "Silence." / "Silence."
```

Narration text is shown as a prompt card on the narrator dashboard.
The narrator taps each phase button, reads the prompt, then continues.

---

## Narrator Capabilities Summary

| Capability | Available |
|---|---|
| See all roles | Yes |
| See all night actions live | Yes |
| Control phase progression | Yes |
| Override submitted actions | No |
| Reveal roles to players | No |
| Remove players mid-game | No (MVP — deferred) |
| Pause game | No (MVP — deferred) |

---

## Real-Time Events (Reverb)

| Event | Channel | Triggered by |
|---|---|---|
| `NightActionSubmitted` | `narrator.{room_id}` | Any player submits night action |
| `PhaseChanged` | `room.{room_id}` | Narrator advances phase |
| `NightResolved` | `room.{room_id}` | Narrator triggers Resolve Night |
| `PlayerEliminated` | `room.{room_id}` | Vote or night resolution |
| `GameFinished` | `room.{room_id}` | WinConditionChecker fires |

---

## App Narrator Mode (Deferred)

Not in MVP scope. To be specced in a future phase.

Will handle:
- Automatic timers per phase
- Automatic role wake/sleep sequencing
- Automatic narration text display
- Auto phase progression
- Same underlying Game Engine — different driver
