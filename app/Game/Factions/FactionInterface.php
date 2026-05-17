<?php

namespace App\Game\Factions;

use App\Models\GameState;
use Illuminate\Support\Collection;

interface FactionInterface
{
    public function getKey(): string;
    public function getName(string $locale): string;
    public function checkWin(GameState $state): bool;
    public function getWinners(GameState $state): Collection;
}
