# specs/lobby-system.md — Lobby System Specification

## Responsibilities

- Create room
- Generate room code and QR code
- Join room via code or QR
- Manage players (list, ready state, remove)
- Manage narrator assignment
- Validate game start conditions

---

## Narration Mode Selection

When creating a room, the host chooses:

| Mode | Host becomes |
|---|---|
| Human Narrator | Narrator — not a player, no role assigned |
| App Narrator | *(deferred — not in MVP)* |

Once the mode is selected and the room is created, it cannot be changed.

---

## Room Creation Flow

1. Host opens the app and taps "Create Room"
2. Host chooses narration mode (Human Narrator for MVP)
3. Room is created in DB with a unique 6-character alphanumeric code
4. A QR code is generated pointing to the join URL (via Ngrok tunnel)
5. Host is assigned as narrator (`is_narrator = true`, `is_host = true`, no role)
6. Lobby screen opens — host waits for players to join

---

## Room Code

- 6 characters, alphanumeric, uppercase (e.g. `W4LF9X`)
- Unique — regenerate on collision
- Stored in `rooms.code`
- Join URL format: `https://{ngrok_domain}/join/{code}`

---

## QR Code

- Generated server-side using a QR library
- Encodes the full join URL
- Displayed on narrator screen for players to scan
- Regenerated if Ngrok tunnel URL changes

---

## Player Join Flow

1. Player scans QR code or enters room code manually
2. App checks: room exists, status is `waiting`, player count below limit
3. Player enters a nickname
4. Nickname uniqueness validated within the room
5. Player record created (`session_token` generated and stored in cookie)
6. Player appears in lobby list on narrator dashboard and all connected screens
7. Narrator sees the join in real time via Reverb broadcast on `room.{room_id}`

---

## Lobby Screen — Narrator View

Displays:
- Room code (large, always visible)
- QR code (scannable)
- Connected player list (nickname, joined time)
- Role configuration panel (see below)
- "Start Game" button (disabled until validation passes)
- Remove player button per player

---

## Role Configuration Panel

The narrator sets how many of each role will be in the game.

Rules:
- Total role count must equal total player count exactly
- At least 1 Werewolf required
- At least 1 Villager or village-faction role required
- Role-specific constraints respected (Two Sisters = exactly 2, etc.)
- Narrator can save a preset configuration for quick reuse (stored in `rooms.settings` JSON)

The panel shows:
- All available roles grouped by faction
- A counter per role (increment / decrement)
- Running total vs player count
- Validation errors inline

---

## Lobby Screen — Player View

Displays:
- Room code
- Waiting message ("En attente du narrateur..." / "Waiting for narrator...")
- List of connected players (nicknames only, no roles)
- Their own nickname highlighted

Players cannot configure anything in the lobby — read only.

---

## Start Game Validation

Before the narrator can start:

| Check | Rule |
|---|---|
| Player count | At least 4 players |
| Role count | Total roles = total players |
| Werewolf presence | At least 1 werewolf-faction role |
| Village presence | At least 1 village-faction role |
| Unique nicknames | No duplicates in room |
| Role constraints | Two Sisters = 2, Three Brothers = 3, solo factions = 1 each |

If any check fails, "Start Game" remains disabled and errors are shown inline.

---

## Game Start Flow

1. Narrator taps "Start Game"
2. Validation runs server-side (LobbyService)
3. Roles are assigned randomly to players (RoleAssignmentService)
4. `game_states` record created with phase = `night`, round = 1
5. Each player's role broadcast privately to `player.{player_id}`
6. All screens transition to the game view
7. Narrator dashboard transitions to NarratorDashboard component

---

## Player Removal

- Narrator can remove a player from the lobby before game start
- Removed player sees a "You have been removed" message
- Their player record is deleted
- Lobby list updates in real time for all

---

## Disconnect Handling (MVP)

- If a player disconnects in the lobby, their record remains for 60 seconds
- If they rejoin with the same session token within 60 seconds, they are restored
- If they do not reconnect, narrator can remove them manually
- No automatic reconnection during an active game in MVP

---

## Real-Time Events (Reverb)

| Event | Channel | Triggered by |
|---|---|---|
| `PlayerJoined` | `room.{room_id}` | Player joins lobby |
| `PlayerLeft` | `room.{room_id}` | Player removed or disconnects |
| `GameStarted` | `room.{room_id}` | Narrator starts game |

---

## Database Interactions

| Action | Tables affected |
|---|---|
| Create room | `rooms` |
| Join room | `players` |
| Configure roles | `rooms.settings` (JSON update) |
| Start game | `game_states`, `players.role_id` |
| Remove player | `players` (delete) |
