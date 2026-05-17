<?php

namespace App\Game\Phases;

use App\Models\GameState;

class FinishedPhase implements PhaseInterface
{
    public function getKey(): string { return 'finished'; }
    public function getName(string $locale): string { return __('ui.phase.finished', [], $locale); }
    public function allowsNightActions(): bool { return false; }
    public function allowsVoting(): bool { return false; }
    public function allowsDiscussion(): bool { return true; }
    public function getNarratorControls(): array { return ['new_game']; }
    public function onEntry(GameState $state): void {}
    public function onExit(GameState $state): void {}
}
