<?php

namespace App\Domain\Tournament;

enum ChampionshipStatus: string
{
    case Draft = 'draft';
    case QuarterFinals = 'quarter_finals';
    case SemiFinals = 'semi_finals';
    case Finals = 'finals';
    case Finished = 'finished';

    public function next(): self
    {
        return match ($this) {
            self::Draft => self::QuarterFinals,
            self::QuarterFinals => self::SemiFinals,
            self::SemiFinals => self::Finals,
            self::Finals => self::Finished,
            self::Finished => throw new \LogicException('Cannot advance beyond Finished status.'),
        };
    }
}
