<?php

namespace App\Game\Phases;

use App\Models\GameState;

interface PhaseInterface
{
    public function getKey(): string;
    public function getName(string $locale): string;
    public function allowsNightActions(): bool;
    public function allowsVoting(): bool;
    public function allowsDiscussion(): bool;
    public function getNarratorControls(): array;
    public function onEntry(GameState $state): void;
    public function onExit(GameState $state): void;
}
