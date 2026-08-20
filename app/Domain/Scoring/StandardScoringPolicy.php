<?php

namespace App\Domain\Scoring;

final class StandardScoringPolicy implements ScoringPolicyInterface
{
    public function pointsFor(int $goalsScored, int $goalsConceded, bool $playingAway): int
    {
        return $goalsScored - $goalsConceded;
    }
}
