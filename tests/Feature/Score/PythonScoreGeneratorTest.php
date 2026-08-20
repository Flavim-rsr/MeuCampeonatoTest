<?php

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Exceptions\ScoreGenerationException;
use App\Infrastructure\Score\PythonScoreGenerator;

test('runs the real teste.py and returns a valid score', function () {
    $score = app(ScoreGeneratorInterface::class)->generate();

    expect($score->home)->toBeGreaterThanOrEqual(0)->toBeLessThan(8)
        ->and($score->away)->toBeGreaterThanOrEqual(0)->toBeLessThan(8);
});

test('container resolves the python adapter', function () {
    expect(app(ScoreGeneratorInterface::class))->toBeInstanceOf(PythonScoreGenerator::class);
});

test('missing script raises a score generation exception', function () {
    new PythonScoreGenerator('python3', base_path('missing.py'))->generate();
})->throws(ScoreGenerationException::class);
