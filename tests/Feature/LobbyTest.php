<?php

namespace Tests\Feature;

use App\Events\PlayerJoined;
use App\Game\Services\LobbyService;
use App\Models\Player;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LobbyTest extends TestCase
{
    use RefreshDatabase;

    private LobbyService $lobbyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->lobbyService = app(LobbyService::class);
    }

    public function test_create_room_generates_unique_code()
    {
        $room = $this->lobbyService->createRoom('HostPlayer');
        $this->assertNotNull($room->code);
        $this->assertEquals(6, strlen($room->code));
        $this->assertEquals('waiting', $room->status);
        $this->assertEquals('human', $room->narration_mode);
        $this->assertEquals('en', $room->settings['locale']);
    }

    public function test_create_room_assigns_host_as_narrator()
    {
        $room = $this->lobbyService->createRoom('HostPlayer');
        $host = $room->host;

        $this->assertTrue($host->is_host);
        $this->assertTrue($host->is_narrator);
        $this->assertNull($host->role_id);
        $this->assertEquals('HostPlayer', $host->nickname);
    }

    public function test_join_room_creates_player_with_session_token()
    {
        $room = $this->lobbyService->createRoom('Host');
        $request = request();

        $player = $this->lobbyService->joinRoom($room, 'Player1', $request);

        $this->assertEquals($room->id, $player->room_id);
        $this->assertEquals('Player1', $player->nickname);
        $this->assertNotNull($player->session_token);
        $this->assertFalse($player->is_narrator);
        $this->assertFalse($player->is_host);
    }

    public function test_join_room_fires_player_joined_event()
    {
        Event::fake();
        $room = $this->lobbyService->createRoom('Host');

        $this->lobbyService->joinRoom($room, 'Player1', request());

        Event::assertDispatched(PlayerJoined::class);
    }

    public function test_join_room_rejects_duplicate_nickname()
    {
        $room = $this->lobbyService->createRoom('Host');
        $this->lobbyService->joinRoom($room, 'Player1', request());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->lobbyService->joinRoom($room, 'Player1', request());
    }

    public function test_join_room_rejects_when_game_started()
    {
        $room = $this->lobbyService->createRoom('Host');
        $room->update(['status' => 'playing']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->lobbyService->joinRoom($room, 'Player1', request());
    }

    public function test_validate_game_start_passes_with_valid_config()
    {
        $room = $this->lobbyService->createRoom('Host');

        foreach (['A', 'B', 'C', 'D'] as $name) {
            $this->lobbyService->joinRoom($room, $name, request());
        }

        $settings = $room->settings ?? [];
        $settings['role_counts'] = [
            'werewolf' => 1,
            'villager' => 1,
            'seer' => 1,
            'witch' => 1,
        ];
        $room->settings = $settings;
        $room->save();

        $errors = $this->lobbyService->validateGameStart($room);
        $this->assertEmpty($errors);
    }

    public function test_validate_game_start_fails_with_less_than_4_players()
    {
        $room = $this->lobbyService->createRoom('Host');
        $errors = $this->lobbyService->validateGameStart($room);
        $this->assertNotEmpty($errors);
    }

    public function test_home_page_has_create_and_join_links()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('rooms.create'));
        $response->assertSee(route('rooms.join'));
    }

    public function test_create_room_page_loads()
    {
        $response = $this->get('/create');
        $response->assertStatus(200);
    }

    public function test_join_room_page_loads()
    {
        $response = $this->get('/join');
        $response->assertStatus(200);
    }

    public function test_narrator_lobby_requires_player_middleware()
    {
        $room = $this->lobbyService->createRoom('Host');
        $response = $this->get("/room/{$room->code}/narrator");
        $response->assertStatus(401);
    }

    public function test_player_lobby_requires_player_middleware()
    {
        $room = $this->lobbyService->createRoom('Host');
        $response = $this->get("/room/{$room->code}/player");
        $response->assertStatus(401);
    }
}
