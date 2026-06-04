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

        if ($token) {
            $player = Player::where('session_token', $token)->first();

            if ($player) {
                $request->merge(['_player' => $player]);
            }
        }

        return $next($request);
    }
}
