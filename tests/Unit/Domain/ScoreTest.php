<?php

use App\Domain\Scoring\Score;

test('detects draw', fn () => expect(new Score(2, 2)->isDraw())->toBeTrue());
test('detects home winner', fn () => expect(new Score(3, 1)->winnerSide())->toBe('home'));
test('detects away winner', fn () => expect(new Score(0, 1)->winnerSide())->toBe('away'));
test('draw has no winner side', fn () => expect(new Score(1, 1)->winnerSide())->toBeNull());
test('rejects negative goals', fn () => new Score(-1, 0))->throws(InvalidArgumentException::class);
