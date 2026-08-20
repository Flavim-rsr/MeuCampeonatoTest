<?php

use App\Domain\Exceptions\ChampionshipRuleViolation;
use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\Phase;

test('enrolls teams with sequential registration order', function () {
    $c = draft();
    $entry = $c->enroll(10, 'Leões');

    expect($entry->registrationOrder)->toBe(1)
        ->and($c->enroll(11, 'Tigres')->registrationOrder)->toBe(2);
});

test('rejects a ninth team', function () {
    $c = draft();
    enrollMany($c, 8);
    $c->enroll(9, 'Nono');
})->throws(ChampionshipRuleViolation::class);

test('rejects duplicated team', function () {
    $c = draft();
    $c->enroll(1, 'A');
    $c->enroll(1, 'A');
})->throws(ChampionshipRuleViolation::class);

test('cannot start with fewer than 8 teams', function () {
    $c = draft();
    enrollMany($c, 7);
    $c->start(identityShuffler());
})->throws(ChampionshipRuleViolation::class);

test('start draws four quarter-final games where each team plays once', function () {
    $c = draft();
    enrollMany($c, 8);
    $c->start(identityShuffler());

    $games = $c->gamesOfPhase(Phase::QuarterFinals);
    $participants = collect($games)->flatMap(fn ($g) => [$g->home->teamId, $g->away->teamId]);

    expect($c->status())->toBe(ChampionshipStatus::QuarterFinals)
        ->and($games)->toHaveCount(4)
        ->and($participants->sort()->values()->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8]);
});

test('cannot enroll after start', function () {
    $c = draft();
    enrollMany($c, 8);
    $c->start(identityShuffler());
    $c->enroll(9, 'Tarde demais');
})->throws(ChampionshipRuleViolation::class);

test('cannot start twice', function () {
    $c = draft();
    enrollMany($c, 8);
    $c->start(identityShuffler());
    $c->start(identityShuffler());
})->throws(ChampionshipRuleViolation::class);
