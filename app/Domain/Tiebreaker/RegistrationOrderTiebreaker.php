<?php

namespace App\Domain\Tiebreaker;

use App\Domain\Tournament\DecidedBy;

final class RegistrationOrderTiebreaker implements TiebreakerStrategyInterface
{
    public function resolve(TiebreakContext $context): TiebreakDecision
    {
        $winner = $context->home->registrationOrder < $context->away->registrationOrder
            ? $context->home
            : $context->away;

        return new TiebreakDecision($winner->teamId, DecidedBy::RegistrationOrder);
    }
}
