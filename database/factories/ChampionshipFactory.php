<?php

namespace Database\Factories;

use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\TiebreakerMode;
use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Championship>
 */
class ChampionshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'status' => ChampionshipStatus::Draft->value,
            'tiebreaker_mode' => TiebreakerMode::Standard->value,
            'scoring_mode' => 'standard',
        ];
    }
}
