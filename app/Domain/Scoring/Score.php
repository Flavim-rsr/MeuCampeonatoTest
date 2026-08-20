<?php

namespace App\Domain\Scoring;

final readonly class Score
{
    public function __construct(public int $home, public int $away)
    {
        if ($home < 0 || $away < 0) {
            throw new \InvalidArgumentException('Goals cannot be negative.');
        }
    }

    public function isDraw(): bool
    {
        return $this->home === $this->away;
    }

    public function winnerSide(): ?string
    {
        return match (true) {
            $this->home > $this->away => 'home',
            $this->away > $this->home => 'away',
            default => null,
        };
    }
}
