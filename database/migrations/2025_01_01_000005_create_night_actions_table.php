<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('night_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_state_id')->constrained();
            $table->foreignId('player_id')->constrained();
            $table->string('action_type');
            $table->foreignId('target_id')->nullable()->constrained('players');
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('night_actions');
    }
};
