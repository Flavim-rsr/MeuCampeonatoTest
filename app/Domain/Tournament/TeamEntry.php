<?php

namespace App\Domain\Tournament;

final readonly class TeamEntry
{
    public function __construct(
        public int $teamId,
        public string $name,
        public int $registrationOrder,
    ) {}
}
