<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoupleBond extends Model
{
    protected $fillable = ['game_state_id', 'player_id', 'partner_id'];

    public function gameState()
    {
        return $this->belongsTo(GameState::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function partner()
    {
        return $this->belongsTo(Player::class, 'partner_id');
    }
}
