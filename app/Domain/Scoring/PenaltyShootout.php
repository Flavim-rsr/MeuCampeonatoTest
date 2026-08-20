<?php

namespace App\Domain\Scoring;

final readonly class PenaltyShootout
{
    public function __construct(public int $home, public int $away)
    {
        if ($home < 0 || $away < 0) {
            throw new \InvalidArgumentException('Penalty goals cannot be negative.');
        }
        if ($home === $away) {
            throw new \InvalidArgumentException('A penalty shootout cannot end in a draw.');
        }
    }

    public function homeWins(): bool
    {
        return $this->home > $this->away;
    }
}
