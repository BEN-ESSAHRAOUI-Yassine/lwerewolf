<?php

namespace App\Game\Engine;

use App\Events\PhaseChanged;
use App\Models\GameState;
use InvalidArgumentException;

class PhaseManager
{
    private array $validTransitions = [
        'waiting' => ['night'],
        'night' => ['day', 'finished'],
        'day' => ['voting'],
        'voting' => ['night', 'day', 'finished'],
        'finished' => [],
    ];

    public function transition(GameState $state, string $toPhase): void
    {
        $from = $state->phase;

        if (!$this->isValidTransition($from, $toPhase)) {
            throw new InvalidArgumentException(
                "Invalid phase transition from '{$from}' to '{$toPhase}'"
            );
        }

        if ($from === 'voting' && $toPhase === 'night') {
            $state->round = ($state->round ?? 1) + 1;
        }

        $state->phase = $toPhase;
        $state->save();

        event(new PhaseChanged($state));
    }

    public function isValidTransition(string $fromPhase, string $toPhase): bool
    {
        $allowed = $this->validTransitions[$fromPhase] ?? [];

        return in_array($toPhase, $allowed, true);
    }

    public function getCurrentPhase(GameState $state): string
    {
        return $state->phase;
    }
}
