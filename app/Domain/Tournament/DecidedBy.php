<?php

namespace App\Domain\Tournament;

enum DecidedBy: string
{
    case Score = 'score';
    case Points = 'points';
    case Penalties = 'penalties';
    case RegistrationOrder = 'registration_order';
}
