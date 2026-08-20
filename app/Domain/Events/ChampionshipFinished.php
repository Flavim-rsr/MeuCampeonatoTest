<?php

namespace App\Domain\Events;

final readonly class ChampionshipFinished
{
    /**
     * @param  array<int, int>  $finalStandings
     */
    public function __construct(
        public int $championshipId,
        public array $finalStandings,
    ) {}
}
