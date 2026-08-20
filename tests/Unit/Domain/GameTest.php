<?php

use App\Domain\Scoring\Score;
use App\Domain\Tournament\DecidedBy;
use App\Domain\Tournament\Game;
use App\Domain\Tournament\Phase;
use App\Domain\Tournament\TeamEntry;

test('play records score, winner and reason', function () {
    $game = new Game(Phase::Final, 1, new TeamEntry(1, 'A', 1), new TeamEntry(2, 'B', 2));
    $game->play(new Score(1, 0), 1, DecidedBy::Score);

    expect($game->isPlayed())->toBeTrue()
        ->and($game->winnerTeamId())->toBe(1)
        ->and($game->loserTeamId())->toBe(2)
        ->and($game->decidedBy())->toBe(DecidedBy::Score);
});

test('cannot play the same game twice', function () {
    $game = new Game(Phase::Final, 1, new TeamEntry(1, 'A', 1), new TeamEntry(2, 'B', 2));
    $game->play(new Score(1, 0), 1, DecidedBy::Score);
    $game->play(new Score(2, 0), 1, DecidedBy::Score);
})->throws(LogicException::class);

test('winner must be one of the participants', function () {
    $game = new Game(Phase::Final, 1, new TeamEntry(1, 'A', 1), new TeamEntry(2, 'B', 2));
    $game->play(new Score(1, 0), 99, DecidedBy::Score);
})->throws(LogicException::class);
