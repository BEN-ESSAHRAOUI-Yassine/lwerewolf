<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couple_bonds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_state_id')->constrained();
            $table->foreignId('player_id')->constrained('players');
            $table->foreignId('partner_id')->constrained('players');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couple_bonds');
    }
};
