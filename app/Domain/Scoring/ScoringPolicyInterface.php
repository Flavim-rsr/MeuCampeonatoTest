<?php

namespace App\Domain\Scoring;

interface ScoringPolicyInterface
{
    public function pointsFor(int $goalsScored, int $goalsConceded, bool $playingAway): int;
}
