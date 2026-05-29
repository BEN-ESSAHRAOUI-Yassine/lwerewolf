<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NgrokHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('ngrok-skip-browser-warning', '1');
        return $response;
    }
}
