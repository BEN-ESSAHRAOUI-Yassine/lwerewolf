<?php

namespace App\Game\Phases;

use App\Models\GameState;

class DayPhase implements PhaseInterface
{
    public function getKey(): string { return 'day'; }
    public function getName(string $locale): string { return __('ui.phase.day', [], $locale); }
    public function allowsNightActions(): bool { return false; }
    public function allowsVoting(): bool { return false; }
    public function allowsDiscussion(): bool { return true; }
    public function getNarratorControls(): array { return ['start_voting']; }
    public function onEntry(GameState $state): void {}
    public function onExit(GameState $state): void {}
}
