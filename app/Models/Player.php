<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'room_id', 'nickname', 'session_token', 'role_id',
        'is_alive', 'is_host', 'is_narrator', 'voting_banned',
    ];

    protected function casts(): array
    {
        return [
            'is_alive' => 'boolean',
            'is_host' => 'boolean',
            'is_narrator' => 'boolean',
            'voting_banned' => 'boolean',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function nightActions()
    {
        return $this->hasMany(NightAction::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    public function coupleBond()
    {
        return $this->hasOne(CoupleBond::class, 'player_id');
    }
}
