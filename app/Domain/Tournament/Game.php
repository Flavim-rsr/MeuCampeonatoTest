<?php

namespace App\Domain\Tournament;

use App\Domain\Scoring\PenaltyShootout;
use App\Domain\Scoring\Score;

final class Game
{
    private ?Score $score = null;

    private ?int $winnerTeamId = null;

    private ?DecidedBy $decidedBy = null;

    private ?PenaltyShootout $penalties = null;

    public function __construct(
        public readonly Phase $phase,
        public readonly int $position,
        public readonly TeamEntry $home,
        public readonly TeamEntry $away,
    ) {}

    public function play(Score $score, int $winnerTeamId, DecidedBy $decidedBy, ?PenaltyShootout $penalties = null): void
    {
        $this->recordScore($score);
        $this->decide($winnerTeamId, $decidedBy, $penalties);
    }

    public function recordScore(Score $score): void
    {
        if ($this->score !== null) {
            throw new \LogicException('This game has already been played.');
        }

        $this->score = $score;
    }

    public function decide(int $winnerTeamId, DecidedBy $decidedBy, ?PenaltyShootout $penalties = null): void
    {
        if ($this->score === null) {
            throw new \LogicException('Cannot decide a game before its score is recorded.');
        }

        if ($this->decidedBy !== null) {
            throw new \LogicException('This game has already been played.');
        }

        if ($winnerTeamId !== $this->home->teamId && $winnerTeamId !== $this->away->teamId) {
            throw new \LogicException('The winner must be one of the game participants.');
        }

        $this->winnerTeamId = $winnerTeamId;
        $this->decidedBy = $decidedBy;
        $this->penalties = $penalties;
    }

    public function isPlayed(): bool
    {
        return $this->decidedBy !== null;
    }

    public function score(): ?Score
    {
        return $this->score;
    }

    public function penalties(): ?PenaltyShootout
    {
        return $this->penalties;
    }

    public function winnerTeamId(): ?int
    {
        return $this->winnerTeamId;
    }

    public function decidedBy(): ?DecidedBy
    {
        return $this->decidedBy;
    }

    public function loserTeamId(): int
    {
        return $this->loserEntry()->teamId;
    }

    public function winnerEntry(): TeamEntry
    {
        if ($this->winnerTeamId === null) {
            throw new \LogicException('This game has not been decided yet.');
        }

        return $this->winnerTeamId === $this->home->teamId ? $this->home : $this->away;
    }

    public function loserEntry(): TeamEntry
    {
        if ($this->winnerTeamId === null) {
            throw new \LogicException('This game has not been decided yet.');
        }

        return $this->winnerTeamId === $this->home->teamId ? $this->away : $this->home;
    }
}
