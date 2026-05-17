<?php

namespace App\Http\Controllers;

use App\Game\Services\VotingService;
use App\Models\Player;
use App\Models\Room;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function submit(Request $request, VotingService $service)
    {
        $player = $request->get('_player');
        if (!$player) abort(401);

        $target = Player::findOrFail($request->input('target_id'));

        $state = $player->room->gameState;
        if (!$state) abort(403);

        $vote = $service->submitVote($player, $target, $state);

        return response()->json([
            'status' => $vote ? 'ok' : 'duplicate',
        ]);
    }
}
