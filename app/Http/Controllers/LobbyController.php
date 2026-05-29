<?php

namespace App\Http\Controllers;

use App\Game\Services\LobbyService;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

class LobbyController extends Controller
{
    public function __construct(
        private LobbyService $lobbyService
    ) {}

    public function create(Request $request)
    {
        $data = $request->validate([
            'nickname' => 'required|string|max:30',
        ]);

        $locale = App::getLocale();
        $room = $this->lobbyService->createRoom($data['nickname'], $locale);

        $player = $room->host;

        cookie()->queue(cookie()->make('session_token', $player->session_token, 1440, '/', null, false, true));

        return redirect()->route('lobby.narrator', $room->code);
    }

    public function join(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
            'nickname' => 'required|string|max:30',
        ]);

        $room = Room::where('code', strtoupper($data['code']))->firstOrFail();

        $player = $this->lobbyService->joinRoom($room, $data['nickname'], $request);

        return redirect()->route('lobby.player', $room->code);
    }
}
