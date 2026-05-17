<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameState extends Model
{
    protected $fillable = ['room_id', 'phase', 'round', 'data'];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'data' => 'array',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function nightActions()
    {
        return $this->hasMany(NightAction::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function coupleBonds()
    {
        return $this->hasMany(CoupleBond::class);
    }
}
