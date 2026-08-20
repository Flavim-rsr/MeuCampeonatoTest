<?php

namespace App\Domain\Tiebreaker;

use App\Domain\Tournament\DecidedBy;

final class PointsTiebreaker implements TiebreakerStrategyInterface
{
    public function resolve(TiebreakContext $context): ?TiebreakDecision
    {
        $homePoints = $context->standings[$context->home->teamId] ?? 0;
        $awayPoints = $context->standings[$context->away->teamId] ?? 0;

        return match (true) {
            $homePoints > $awayPoints => new TiebreakDecision($context->home->teamId, DecidedBy::Points),
            $awayPoints > $homePoints => new TiebreakDecision($context->away->teamId, DecidedBy::Points),
            default => null,
        };
    }
}
