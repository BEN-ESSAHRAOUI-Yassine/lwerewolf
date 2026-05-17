<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = ['game_state_id', 'voter_id', 'target_id'];

    public function gameState()
    {
        return $this->belongsTo(GameState::class);
    }

    public function voter()
    {
        return $this->belongsTo(Player::class, 'voter_id');
    }

    public function target()
    {
        return $this->belongsTo(Player::class, 'target_id');
    }
}
