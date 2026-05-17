<?php

namespace App\Http\Middleware;

use App\Models\Player;
use Closure;
use Illuminate\Http\Request;

class IdentifyPlayer
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('session_token');

        if (!$token) {
            abort(401, 'No session token');
        }

        $player = Player::where('session_token', $token)->first();

        if (!$player) {
            abort(401, 'Invalid session token');
        }

        $request->merge(['_player' => $player]);

        return $next($request);
    }
}
