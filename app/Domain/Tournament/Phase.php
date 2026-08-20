<?php

namespace App\Domain\Tournament;

enum Phase: string
{
    case QuarterFinals = 'quarter_finals';
    case SemiFinals = 'semi_finals';
    case ThirdPlace = 'third_place';
    case Final = 'final';
}
