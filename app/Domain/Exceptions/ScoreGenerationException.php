<?php

namespace App\Domain\Exceptions;

final class ScoreGenerationException extends \RuntimeException
{
    public static function fromReason(string $reason): self
    {
        return new self($reason);
    }
}
