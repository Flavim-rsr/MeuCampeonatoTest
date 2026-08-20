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
        Schema::create('championships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->string('tiebreaker_mode')->default('default');
            $table->string('scoring_mode')->default('standard');
            $table->foreignId('first_place_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('second_place_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('third_place_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('fourth_place_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('championships');
    }
};
