<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained()->cascadeOnDelete();
            $table->string('phase');
            $table->tinyInteger('position')->unsigned();
            $table->foreignId('home_team_id')->constrained('teams');
            $table->foreignId('away_team_id')->constrained('teams');
            $table->tinyInteger('home_score')->unsigned()->nullable();
            $table->tinyInteger('away_score')->unsigned()->nullable();
            $table->tinyInteger('penalty_home')->unsigned()->nullable();
            $table->tinyInteger('penalty_away')->unsigned()->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams');
            $table->string('decided_by')->nullable();
            $table->timestamps();

            $table->unique(['championship_id', 'phase', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
