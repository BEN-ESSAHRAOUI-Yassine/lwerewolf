<?php

namespace App\Game\Actions;

use App\Models\GameState;
use App\Models\Player;

interface ActionInterface
{
    public function getActingRole(): string;
    public function getTarget(): ?Player;
    public function isValid(GameState $state): bool;
    public function resolve(GameState $state): void;
    public function getPriority(): int;
}
