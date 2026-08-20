<?php

namespace App\Domain\Tiebreaker;

interface TiebreakerStrategyInterface
{
    public function resolve(TiebreakContext $context): ?TiebreakDecision;
}
