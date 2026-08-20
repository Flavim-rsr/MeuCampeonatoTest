<?php

use App\Domain\Scoring\PenaltyShootout;

test('rejects drawn shootout', fn () => new PenaltyShootout(3, 3))->throws(InvalidArgumentException::class);
test('knows home winner', fn () => expect(new PenaltyShootout(5, 4)->homeWins())->toBeTrue());
