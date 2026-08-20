<?php

namespace App\Domain\Tiebreaker;

use App\Domain\Tournament\TeamEntry;

final readonly class TiebreakContext
{
    /** @param array<int, int> $standings */
    public function __construct(
        public TeamEntry $home,
        public TeamEntry $away,
        public array $standings,
    ) {}
}
