<?php

namespace Tests\Doubles;

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Scoring\Score;

final class FakeScoreGenerator implements ScoreGeneratorInterface
{
    private int $calls = 0;

    /** @param list<array{0: int, 1: int}> $pairs */
    public function __construct(private readonly array $pairs) {}

    public function generate(): Score
    {
        [$home, $away] = $this->pairs[$this->calls % count($this->pairs)];
        $this->calls++;

        return new Score($home, $away);
    }
}
