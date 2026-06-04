<?php

namespace App\Game\Services;

use App\Events\LoverDied;
use App\Events\PlayerEliminated;
use App\Events\VillageIdiotRevealed;
use App\Events\VoteSubmitted;
use App\Game\Engine\WinConditionChecker;
use App\Models\CoupleBond;
use App\Models\GameState;
use App\Models\Player;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

class VotingService
{
    public function __construct(
        private WinConditionChecker $winChecker,
    ) {}

    public function submitVote(Player $voter, Player $target, GameState $state): ?Vote
    {
        if ($voter->is_narrator) abort(403);
        if (!$voter->is_alive) abort(403);
        if ($voter->voting_banned) abort(403);
        if ($state->phase !== 'voting') abort(403);

        $data = $state->data ?? [];
        $voteBanList = $data['vote_ban_next_round'] ?? [];
        if (in_array($voter->id, $voteBanList)) abort(403);

        $alreadyVoted = Vote::where('game_state_id', $state->id)
            ->where('voter_id', $voter->id)
            ->exists();
        if ($alreadyVoted) return null;

        if (!$target->is_alive) abort(403);
        if ($target->id === $voter->id) abort(403);
        if ($target->room_id !== $voter->room_id) abort(403);

        $vote = Vote::create([
            'game_state_id' => $state->id,
            'voter_id' => $voter->id,
            'target_id' => $target->id,
        ]);

        event(new VoteSubmitted($vote));

        return $vote;
    }

    public function tally(GameState $state): array
    {
        $votes = Vote::where('game_state_id', $state->id)->get();
        $counts = [];

        foreach ($votes as $vote) {
            $targetId = $vote->target_id;
            $counts[$targetId] = ($counts[$targetId] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    public function resolve(GameState $state): ?\App\Game\Factions\FactionInterface
    {
        return DB::transaction(function () use ($state) {
            $tally = $this->tally($state);
            $maxVotes = empty($tally) ? 0 : max($tally);

            if ($maxVotes === 0) {
                return $this->winChecker->check($state);
            }

            $topTargetIds = array_keys($tally, $maxVotes);

            if (count($topTargetIds) > 1) {
                return $this->handleTie($state, $topTargetIds);
            }

            $targetId = $topTargetIds[0];
            $target = Player::find($targetId);
            if (!$target) return null;

            return $this->eliminateOrSpare($state, $target);
        });
    }

    private function handleTie(GameState $state, array $tiedIds): ?\App\Game\Factions\FactionInterface
    {
        $alivePlayers = Player::where('room_id', $state->room_id)
            ->where('is_alive', true)
            ->where('is_narrator', false)
            ->with('role')
            ->get();

        $scapegoat = $alivePlayers->firstWhere('role.key', 'scapegoat');

        if ($scapegoat) {
            $data = $state->data ?? [];
            $data['scapegoat_eliminated_by_tie'] = true;
            $state->data = $data;
            $state->save();

            return $this->eliminatePlayerWithChain($state, $scapegoat);
        }

        return $this->winChecker->check($state);
    }

    private function eliminateOrSpare(GameState $state, Player $target): ?\App\Game\Factions\FactionInterface
    {
        $role = $target->role;

        if ($role && $role->key === 'village_idiot') {
            $target->voting_banned = true;
            $target->save();

            event(new VillageIdiotRevealed($target));

            return $this->winChecker->check($state);
        }

        if ($role && $role->key === 'elder') {
            $data = $state->data ?? [];
            if (empty($data['elder_first_attack_survived'])) {
                $data['elder_first_attack_survived'] = true;
                $data['elder_abilities_disabled'] = true;
                $state->data = $data;
                $state->save();
            }
        }

        return $this->eliminatePlayerWithChain($state, $target);
    }

    private function eliminatePlayerWithChain(GameState $state, Player $player): ?\App\Game\Factions\FactionInterface
    {
        $role = $player->role;

        if ($role && $role->key === 'knight_with_rusty_sword') {
            $data = $state->data ?? [];
            $data['knight_killed_by_werewolf'] = false;
            $state->data = $data;
            $state->save();
        }

        $checkAngel = $role && $role->key === 'angel' && $state->round === 1;

        return $this->applyDeathWithChain($state, $player, $checkAngel);
    }

    private function applyDeathWithChain(GameState $state, Player $player, bool $checkAngel = false): ?\App\Game\Factions\FactionInterface
    {
        $toProcess = [[$player, $checkAngel]];
        $processedIds = [];

        while (!empty($toProcess)) {
            [$current, $checkAngelForThis] = array_shift($toProcess);
            if (in_array($current->id, $processedIds)) continue;
            $processedIds[] = $current->id;

            $role = $current->role;

            if ($role && $role->key === 'hunter') {
                $current->is_alive = false;
                $current->save();
                event(new PlayerEliminated($current));

                $winner = $this->winChecker->check($state);
                if ($winner) return $winner;

                continue;
            }

            $current->is_alive = false;
            $current->save();
            event(new PlayerEliminated($current));

            if ($checkAngelForThis && $role && $role->key === 'angel') {
                $data = $state->data ?? [];
                $data['angel_eliminated_by_vote'] = true;
                $state->data = $data;
                $state->save();
            }

            $winner = $this->winChecker->check($state);
            if ($winner) return $winner;

            $bond = CoupleBond::where('game_state_id', $state->id)
                ->where(function ($q) use ($current) {
                    $q->where('player_id', $current->id)
                      ->orWhere('partner_id', $current->id);
                })->first();

            if ($bond) {
                $partnerId = $bond->player_id === $current->id ? $bond->partner_id : $bond->player_id;
                $partner = Player::find($partnerId);

                if ($partner && $partner->is_alive) {
                    event(new LoverDied($current, $partner));
                    $toProcess[] = [$partner, false];
                }
            }
        }

        return null;
    }
}
