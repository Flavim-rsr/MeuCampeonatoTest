<?php

namespace App\Domain\Scoring;

use App\Domain\Tournament\Game;

final class StandingsCalculator
{
    public function __construct(private readonly ScoringPolicyInterface $policy) {}

    /**
     * @param  array<int, Game>  $games
     * @return array<int, int>
     */
    public function calculate(array $games): array
    {
        $standings = [];

        foreach ($games as $game) {
            $score = $game->score();

            if ($score === null) {
                continue;
            }

            $homeId = $game->home->teamId;
            $awayId = $game->away->teamId;

            $standings[$homeId] = ($standings[$homeId] ?? 0)
                + $this->policy->pointsFor($score->home, $score->away, false);

            $standings[$awayId] = ($standings[$awayId] ?? 0)
                + $this->policy->pointsFor($score->away, $score->home, true);
        }

        return $standings;
    }
}
