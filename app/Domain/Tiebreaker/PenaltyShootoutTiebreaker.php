<?php

namespace App\Domain\Tiebreaker;

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Scoring\PenaltyShootout;
use App\Domain\Tournament\DecidedBy;

final class PenaltyShootoutTiebreaker implements TiebreakerStrategyInterface
{
    public function __construct(
        private readonly ScoreGeneratorInterface $scores,
        private readonly int $maxAttempts = 5,
    ) {}

    public function resolve(TiebreakContext $context): ?TiebreakDecision
    {
        for ($attempt = 0; $attempt < $this->maxAttempts; $attempt++) {
            $score = $this->scores->generate();

            if ($score->isDraw()) {
                continue;
            }

            $penalties = new PenaltyShootout($score->home, $score->away);
            $winner = $penalties->homeWins() ? $context->home : $context->away;

            return new TiebreakDecision($winner->teamId, DecidedBy::Penalties, $penalties);
        }

        return null;
    }
}
