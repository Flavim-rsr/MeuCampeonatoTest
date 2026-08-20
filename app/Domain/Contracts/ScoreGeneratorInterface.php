<?php

namespace App\Domain\Contracts;

use App\Domain\Scoring\Score;

interface ScoreGeneratorInterface
{
    public function generate(): Score;
}
