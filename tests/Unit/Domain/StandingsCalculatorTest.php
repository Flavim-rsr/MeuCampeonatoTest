<?php

use App\Domain\Scoring\Score;
use App\Domain\Scoring\StandardScoringPolicy;
use App\Domain\Scoring\StandingsCalculator;
use App\Domain\Tournament\DecidedBy;
use App\Domain\Tournament\Game;
use App\Domain\Tournament\Phase;
use App\Domain\Tournament\TeamEntry;

function entry(int $id, int $order = 1): TeamEntry
{
    return new TeamEntry($id, "Team {$id}", $order);
}

test('accumulates +1 per goal scored and -1 per goal conceded', function () {
    $calc = new StandingsCalculator(new StandardScoringPolicy);
    $g1 = new Game(Phase::QuarterFinals, 1, entry(1), entry(2));
    $g1->play(new Score(3, 1), 1, DecidedBy::Score);
    $g2 = new Game(Phase::QuarterFinals, 2, entry(3), entry(4));
    $g2->play(new Score(0, 2), 4, DecidedBy::Score);

    expect($calc->calculate([$g1, $g2]))->toBe([1 => 2, 2 => -2, 3 => -2, 4 => 2]);
});

test('includes drawn games with recorded score but no winner yet', function () {
    $calc = new StandingsCalculator(new StandardScoringPolicy);
    $game = new Game(Phase::QuarterFinals, 1, entry(1), entry(2));
    $game->recordScore(new Score(2, 2));

    expect($calc->calculate([$game]))->toBe([1 => 0, 2 => 0]);
});

test('ignores games without score', function () {
    $calc = new StandingsCalculator(new StandardScoringPolicy);
    $game = new Game(Phase::QuarterFinals, 1, entry(1), entry(2));

    expect($calc->calculate([$game]))->toBe([]);
});
