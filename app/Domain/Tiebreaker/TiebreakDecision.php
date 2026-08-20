<?php

namespace App\Domain\Tiebreaker;

use App\Domain\Scoring\PenaltyShootout;
use App\Domain\Tournament\DecidedBy;

final readonly class TiebreakDecision
{
    public function __construct(
        public int $winnerTeamId,
        public DecidedBy $decidedBy,
        public ?PenaltyShootout $penalties = null,
    ) {}
}
