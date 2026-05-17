<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['code', 'host_player_id', 'status', 'narration_mode', 'settings'];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function host()
    {
        return $this->belongsTo(Player::class, 'host_player_id');
    }

    public function gameState()
    {
        return $this->hasOne(GameState::class);
    }
}
