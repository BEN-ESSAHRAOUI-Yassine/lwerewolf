<?php

namespace App\Game\Phases;

use App\Models\GameState;

class WaitingPhase implements PhaseInterface
{
    public function getKey(): string { return 'waiting'; }
    public function getName(string $locale): string { return __('ui.phase.waiting', [], $locale); }
    public function allowsNightActions(): bool { return false; }
    public function allowsVoting(): bool { return false; }
    public function allowsDiscussion(): bool { return true; }
    public function getNarratorControls(): array { return ['start_game']; }
    public function onEntry(GameState $state): void {}
    public function onExit(GameState $state): void {}
}
