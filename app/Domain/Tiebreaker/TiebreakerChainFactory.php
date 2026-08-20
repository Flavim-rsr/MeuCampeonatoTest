<?php

namespace App\Domain\Tiebreaker;

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Tournament\TiebreakerMode;

final class TiebreakerChainFactory
{
    public static function forMode(TiebreakerMode $mode, ScoreGeneratorInterface $scores): TiebreakerChain
    {
        return match ($mode) {
            TiebreakerMode::Standard => new TiebreakerChain(
                new PointsTiebreaker,
                new RegistrationOrderTiebreaker,
            ),
            TiebreakerMode::Penalties => new TiebreakerChain(
                new PointsTiebreaker,
                new PenaltyShootoutTiebreaker($scores),
                new RegistrationOrderTiebreaker,
            ),
        };
    }
}
