<?php

namespace App\Game\Phases;

use App\Models\GameState;

class NightPhase implements PhaseInterface
{
    public function getKey(): string { return 'night'; }
    public function getName(string $locale): string { return __('ui.phase.night', [], $locale); }
    public function allowsNightActions(): bool { return true; }
    public function allowsVoting(): bool { return false; }
    public function allowsDiscussion(): bool { return false; }
    public function getNarratorControls(): array { return ['resolve_night', 'little_girl_caught']; }
    public function onEntry(GameState $state): void {}
    public function onExit(GameState $state): void {}
}
