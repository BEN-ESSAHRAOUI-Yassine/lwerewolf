<?php

use App\Http\Controllers\LobbyController;
use App\Http\Controllers\VoteController;
use App\Livewire\Lobby\CreateRoom;
use App\Livewire\Lobby\JoinRoom;
use App\Livewire\Narrator\NarratorDashboard;
use App\Livewire\Narrator\NarratorLobby;
use App\Livewire\Player\PlayerGameView;
use App\Livewire\Player\PlayerLobby;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'fr'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return redirect(route('home'));
})->name('locale.switch');

Route::get('/room/{room}/narrator', NarratorLobby::class)->name('lobby.narrator');
Route::get('/room/{room}/player', PlayerLobby::class)->name('lobby.player');
Route::get('/game/{room}/narrator', NarratorDashboard::class)->name('game.narrator');
Route::get('/game/{room}/player', PlayerGameView::class)->name('game.player');

Route::get('/create', CreateRoom::class)->name('rooms.create');
Route::get('/join/{code?}', JoinRoom::class)->name('rooms.join');

Route::post('/api/rooms', [LobbyController::class, 'create'])->name('api.rooms.create');
Route::post('/api/rooms/join', [LobbyController::class, 'join'])->name('api.rooms.join');
Route::post('/api/vote', [VoteController::class, 'submit'])->name('api.vote.submit');
