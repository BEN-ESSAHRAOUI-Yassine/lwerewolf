<?php

namespace App\Game\Phases;

use App\Models\GameState;

class VotingPhase implements PhaseInterface
{
    public function getKey(): string { return 'voting'; }
    public function getName(string $locale): string { return __('ui.phase.voting', [], $locale); }
    public function allowsNightActions(): bool { return false; }
    public function allowsVoting(): bool { return true; }
    public function allowsDiscussion(): bool { return false; }
    public function getNarratorControls(): array { return ['resolve_vote', 'trigger_second_vote']; }
    public function onEntry(GameState $state): void {}
    public function onExit(GameState $state): void {}
}
