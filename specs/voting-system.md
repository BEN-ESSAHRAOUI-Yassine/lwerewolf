# specs/voting-system.md — Voting System Specification

## Responsibilities

- Collect player votes during Voting phase
- Enforce voting rules (alive only, one vote per round, ban enforcement)
- Calculate result
- Handle ties
- Handle Stuttering Judge second vote
- Handle Scapegoat tie override
- Apply elimination and trigger death chain
- Check win conditions after elimination

---

## Voting Flow

1. Narrator taps "Start Voting" on dashboard
2. Phase transitions to `voting`
3. All eligible players receive voting panel on their screen
4. Players submit one vote (secret — other players cannot see votes)
5. Narrator dashboard shows live vote count per candidate (no names, just counts) — full detail visible to narrator only
6. Narrator taps "Close Voting"
7. VotingService calculates result
8. Narrator taps "Announce Elimination" — result broadcast to all screens
9. Elimination applied, death chain runs, WinConditionChecker runs
10. If no win: narrator taps "Start Night" to advance

---

## Eligibility Rules

| Rule | Detail |
|---|---|
| Dead players cannot vote | `is_alive = false` → no voting panel shown |
| Village Idiot ban | If `voting_banned = true` on player → no voting panel shown |
| One vote per phase | Once submitted, vote is final — no changes |
| Must vote for living player | Dead players not shown as vote targets |
| Cannot vote for self | Self excluded from target list |

---

## Vote Submission

- Player selects a target from the list of living players (excluding self)
- Anti-misclick: confirmation step required before final submission ("Confirmer?" / "Confirm?")
- Vote stored in `votes` table
- `VoteSubmitted` event fired → narrator dashboard updates live

---

## Vote Calculation

Handled by `VotingService::calculateResult(GameState $state)`.

Steps:
1. Count votes per target
2. Find target(s) with highest vote count
3. If one clear winner → proceed to elimination
4. If tie → apply tie rule (see below)

---

## Tie Rules

### Scapegoat Present (alive)

If a tie occurs and the Scapegoat is alive:
- Scapegoat is eliminated instead of the tied players
- Scapegoat submits "last decree" — chooses which players may or may not vote next round
- Voted players are spared
- `PlayerEliminated` fired for Scapegoat
- Scapegoat's last decree stored in `game_states.data` as `vote_ban_next_round: [player_ids]`

### No Scapegoat

Default tie behavior: **no elimination this round** (configurable in room settings).

Optional configurable alternatives (narrator sets at room creation):
- Re-vote between tied players only
- Random elimination among tied players

---

## Stuttering Judge — Second Vote

If the Stuttering Judge is alive and has not yet used their ability:
- They submit a secret signal via their player screen during the voting phase
- Narrator sees the signal on the dashboard (live feed)
- After the first vote result is announced and elimination applied:
  - Narrator taps "Trigger Second Vote"
  - A full second vote immediately begins
  - Same rules apply
  - `stuttering_judge_used` set to `true` in `game_states.data`

---

## Village Idiot — Survival on Vote

When Village Idiot receives the most votes:
1. Narrator announces the vote result
2. Instead of elimination: Village Idiot's role is publicly revealed
3. Village Idiot survives
4. `voting_banned = true` set on their player record
5. `PlayerEliminated` is NOT fired — they are alive
6. A special `VillageIdiotRevealed` event is fired instead
7. Next voting phase: Village Idiot has no voting panel

---

## Elder — Survival on First Vote

If the Elder is voted out for the first time:
- Elder survives (resilience ability)
- `elder_first_attack_survived = true` set in `game_states.data`
- **All village special abilities are permanently disabled** (configurable rule)
- `elder_abilities_disabled = true` set in `game_states.data`
- If Elder is voted out a second time: normal elimination

---

## Devoted Servant — Pre-Reveal Swap

When any player is voted out, before the narrator announces their role:
1. Devoted Servant (if alive and ability not used) receives a private prompt
2. They have a short window to decide: swap or pass
3. If swap:
   - Voted player takes Devoted Servant's identity, is eliminated as "Devoted Servant"
   - Devoted Servant takes voted player's role, stays alive with new role
   - Narrator is informed privately via dashboard before public announcement
   - Ability is consumed (tracked in `game_states.data`)
4. If pass: normal elimination proceeds

---

## Death Chain on Vote Elimination

After a player is confirmed eliminated by vote:

1. Check if player is a Lover → partner dies (LoverDied event, death chain continues)
2. Check if player is Hunter → Hunter shot triggered (narrator prompts Hunter player)
3. Check if player is Angel + round 1 → WinConditionChecker fires Angel check immediately
4. Apply death (`is_alive = false`)
5. Fire `PlayerEliminated`
6. Process queued deaths
7. Run WinConditionChecker

---

## Narrator Dashboard During Voting

| Element | Detail |
|---|---|
| Vote progress | X / Y players have voted (count only, no names) |
| Per-candidate count | Number of votes each candidate has received (narrator only) |
| Live feed | Each vote submission shown as "A player voted" (anonymous to others) |
| Close Voting button | Available once at least 1 vote submitted (narrator decides timing) |
| Trigger Second Vote | Only visible if Stuttering Judge signaled |

---

## Player Voting Panel

| Element | Detail |
|---|---|
| Player list | All living players except self |
| Selection | Tap to select candidate |
| Confirmation | Confirmation prompt before final submission |
| Submitted state | Shows "Vote submitted" — no further action |
| Banned state | Shows "You cannot vote this round" for Village Idiot / last decree ban |

---

## Real-Time Events (Reverb)

| Event | Channel | Triggered by |
|---|---|---|
| `VoteSubmitted` | `narrator.{room_id}` | Player submits vote |
| `VotingClosed` | `room.{room_id}` | Narrator closes voting |
| `PlayerEliminated` | `room.{room_id}` | Elimination confirmed |
| `VillageIdiotRevealed` | `room.{room_id}` | Village Idiot voted out |
| `SecondVoteTriggered` | `room.{room_id}` | Stuttering Judge signal executed |
| `GameFinished` | `room.{room_id}` | Win condition met post-vote |

---

## Database Interactions

| Action | Tables affected |
|---|---|
| Submit vote | `votes` |
| Apply elimination | `players.is_alive` |
| Apply vote ban | `players.voting_banned` |
| Apply last decree ban | `game_states.data` |
| Mark judge used | `game_states.data` |
| Mark devoted servant swap | `game_states.data`, `players.role_id` |
