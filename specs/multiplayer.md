# specs/multiplayer.md — Multiplayer Specification

## MVP Multiplayer Model

Players are physically together in the same location.
The host device runs the Laravel server locally, exposed via Ngrok tunnel.
All players connect to the same server via QR code or room code over the internet (Ngrok).

No account system. No cloud hosting. No matchmaking.

---

## Architecture

```
Host Device (Laravel server + Ngrok)
        │
        │  HTTPS + WebSocket (Ngrok tunnel)
        │
   ┌────┴────────────────────────┐
   │                             │
Player Device 1           Player Device 2 ...
(Browser / PWA)           (Browser / PWA)
```

### Host Device

- Runs `php artisan serve` locally
- Ngrok exposes the local server to the internet
- Ngrok URL is embedded in the room QR code
- Host device also runs Laravel Reverb for WebSocket connections
- All game state lives on the host device DB (SQLite for MVP simplicity)

### Client Devices

- Players open the Ngrok URL in their mobile browser
- No app install required (PWA-ready but not required for MVP)
- Lightweight — only Livewire + Reverb subscription on each client
- Clients receive state via WebSocket, submit actions via HTTP to host

---

## Ngrok Configuration

- One Ngrok tunnel per session
- Ngrok URL changes each session unless using a paid static domain
- QR code is regenerated when the session starts
- Narrator's lobby screen always shows the current valid QR and room code
- Room code is a secondary manual join method in case QR fails

### Recommended Ngrok Setup

```bash
# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000

# Start Reverb
php artisan reverb:start

# Start Ngrok tunnel
ngrok http 8000
```

The resulting Ngrok URL (e.g. `https://abc123.ngrok-free.app`) is stored in `rooms.settings`
as `ngrok_url` and used to generate the QR code.

---

## Session / Identity

No accounts required. Players are identified by a `session_token` (random UUID) generated
at join time and stored in a cookie on their device.

```
Player joins → session_token generated → stored in:
  - DB: players.session_token
  - Client: cookie (httpOnly, same-site)
```

On every request the session_token is verified against the DB to identify the player.
If token missing or invalid: player is redirected to join screen.

---

## WebSocket Channels (Reverb)

All channels are private (authenticated).

| Channel | Subscribers | Content |
|---|---|---|
| `room.{room_id}` | All players + narrator | Phase changes, eliminations, game events |
| `narrator.{room_id}` | Narrator only | Live action feed, full player info |
| `player.{player_id}` | Player only | Role card, night action, private results |
| `werewolves.{room_id}` | Werewolf-faction players | Shared kill coordination, identity reveal |

### Channel Authentication

Laravel Echo + Reverb channel auth via `routes/channels.php`:

```php
// Player channel — only the owning player
Broadcast::channel('player.{playerId}', function ($request, $playerId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player && $player->id === (int) $playerId;
});

// Narrator channel — only narrator of this room
Broadcast::channel('narrator.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))->first();
    return $player && $player->room_id === (int) $roomId && $player->is_narrator;
});

// Werewolf channel — only werewolf-faction players in this room
Broadcast::channel('werewolves.{roomId}', function ($request, $roomId) {
    $player = Player::where('session_token', $request->cookie('session_token'))
                    ->with('role')->first();
    return $player
        && $player->room_id === (int) $roomId
        && $player->role
        && $player->role->faction === 'werewolves';
});
```

---

## Reconnection Handling (MVP)

### During Lobby

- Player disconnects: record kept for 60 seconds
- Player reconnects with same session_token within 60 seconds: restored silently
- After 60 seconds: narrator can remove manually

### During Game

- Player disconnects: their record and role persist in DB
- Player can reconnect by visiting the same URL with their cookie still valid
- On reconnect: they are shown their current game state (phase, role card, etc.)
- No automatic "rejoin" prompt — reconnect is transparent
- Narrator is not alerted on reconnect (MVP)

### Narrator Disconnects

- If narrator disconnects during game: game is paused implicitly (no phase controls available)
- Narrator reconnects and resumes from current state
- No host migration in MVP

---

## Latency Considerations

Since all players are in the same physical location on Ngrok:
- Expected round-trip latency: 50–200ms (acceptable)
- Action submissions are HTTP POST (reliable)
- State updates are WebSocket push (near-instant)
- No optimistic UI needed for MVP

---

## Player Limits

| Limit | Value |
|---|---|
| Minimum players | 4 |
| Recommended maximum | 18 |
| Hard maximum (MVP) | 24 |

Limits enforced at join time and in start game validation.

---

## Database (SQLite for MVP)

SQLite runs locally on the host device. No external DB server needed.

```
config/database.php → default: sqlite
database/database.sqlite → auto-created on first migrate
```

This keeps setup minimal — no MySQL or PostgreSQL needed for local MVP sessions.

---

## Future Multiplayer (Post-MVP)

- Cloud hosting (Laravel Forge / Vapor)
- Persistent rooms
- Internet play without physical co-location
- Spectator mode
- Reconnection with host migration
- Multiple concurrent rooms on shared server
