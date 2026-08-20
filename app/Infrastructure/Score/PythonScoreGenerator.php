<?php

namespace App\Infrastructure\Score;

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Exceptions\ScoreGenerationException;
use App\Domain\Scoring\Score;
use Illuminate\Support\Facades\Process;
use Throwable;

final class PythonScoreGenerator implements ScoreGeneratorInterface
{
    public function __construct(
        private readonly string $binary,
        private readonly string $scriptPath,
    ) {}

    public function generate(): Score
    {
        if (! is_file($this->scriptPath)) {
            throw ScoreGenerationException::fromReason(
                "Simulation script not found at [{$this->scriptPath}]."
            );
        }

        try {
            $result = Process::timeout(2)->run([$this->binary, $this->scriptPath]);
        } catch (Throwable $e) {
            throw ScoreGenerationException::fromReason(
                "Failed to execute simulation script: {$e->getMessage()}"
            );
        }

        if ($result->failed()) {
            throw ScoreGenerationException::fromReason(
                "Simulation script exited with code {$result->exitCode()}: {$result->errorOutput()}"
            );
        }

        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $result->output())),
            fn (string $line): bool => $line !== ''
        ));

        if (count($lines) < 2) {
            throw ScoreGenerationException::fromReason(
                'Simulation script did not produce two output lines.'
            );
        }

        [$homeLine, $awayLine] = $lines;

        if (! ctype_digit($homeLine) || ! ctype_digit($awayLine)) {
            throw ScoreGenerationException::fromReason(
                "Simulation script produced non-numeric output: [{$homeLine}, {$awayLine}]."
            );
        }

        return new Score((int) $homeLine, (int) $awayLine);
    }
}
