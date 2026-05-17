<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightAction extends Model
{
    protected $fillable = [
        'game_state_id', 'player_id', 'action_type',
        'target_id', 'metadata', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function gameState()
    {
        return $this->belongsTo(GameState::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function target()
    {
        return $this->belongsTo(Player::class, 'target_id');
    }
}
