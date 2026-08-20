<?php

namespace App\Domain\Exceptions;

final class ChampionshipRuleViolation extends \DomainException
{
    public static function notInDraft(): self
    {
        return new self('Teams can only be enrolled while the championship is in draft.');
    }

    public static function enrollmentLimitReached(): self
    {
        return new self('A championship must have exactly 8 teams; enrollment limit reached.');
    }

    public static function teamAlreadyEnrolled(): self
    {
        return new self('This team is already enrolled in the championship.');
    }

    public static function wrongTeamCount(int $count): self
    {
        return new self("A championship needs exactly 8 teams to start, {$count} enrolled.");
    }

    public static function invalidTransition(string $from): self
    {
        return new self("No phase can be simulated while the championship is in status '{$from}'.");
    }
}
