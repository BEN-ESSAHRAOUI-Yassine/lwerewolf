<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['key', 'faction', 'night_order', 'abilities', 'win_condition'];

    protected function casts(): array
    {
        return [
            'night_order' => 'integer',
            'abilities' => 'array',
        ];
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }
}
