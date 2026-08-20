<?php

namespace App\Domain\Tournament;

enum TiebreakerMode: string
{
    case Standard = 'default';
    case Penalties = 'penalties';
}
