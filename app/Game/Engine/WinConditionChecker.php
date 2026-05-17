<?php

namespace App\Game\Engine;

use App\Game\Factions\AngelFaction;
use App\Game\Factions\FactionInterface;
use App\Game\Factions\LoversFaction;
use App\Game\Factions\PiedPiperFaction;
use App\Game\Factions\VillageFaction;
use App\Game\Factions\WerewolvesFaction;
use App\Game\Factions\WhiteWerewolfFaction;
use App\Models\GameState;

class WinConditionChecker
{
    private array $factions;

    public function __construct()
    {
        $this->factions = [
            new AngelFaction(),
            new WhiteWerewolfFaction(),
            new PiedPiperFaction(),
            new WerewolvesFaction(),
            new VillageFaction(),
            new LoversFaction(),
        ];
    }

    public function check(GameState $state): ?FactionInterface
    {
        foreach ($this->factions as $faction) {
            if ($faction->checkWin($state)) {
                return $faction;
            }
        }

        return null;
    }
}
