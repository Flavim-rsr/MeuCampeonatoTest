<?php

use App\Domain\Exceptions\ChampionshipRuleViolation;
use App\Domain\Scoring\StandardScoringPolicy;
use App\Domain\Scoring\StandingsCalculator;
use App\Domain\Tiebreaker\TiebreakerChainFactory;
use App\Domain\Tournament\Championship;
use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\DecidedBy;
use App\Domain\Tournament\Phase;
use Tests\Doubles\FakeScoreGenerator;

function simulate(Championship $c, FakeScoreGenerator $scores): void
{
    $chain = TiebreakerChainFactory::forMode($c->tiebreakerMode, $scores);
    $c->simulateCurrentPhase($scores, $chain, new StandingsCalculator(new StandardScoringPolicy), identityShuffler());
}

function startedChampionship(): Championship
{
    $c = draft();
    enrollMany($c, 8);
    $c->start(identityShuffler());

    return $c;
}

test('simulating quarter finals plays 4 games and draws semi finals', function () {
    $c = startedChampionship();
    simulate($c, new FakeScoreGenerator([[2, 1]]));  // home sempre vence

    expect($c->status())->toBe(ChampionshipStatus::SemiFinals)
        ->and($c->gamesOfPhase(Phase::SemiFinals))->toHaveCount(2);
});

test('semi final losers go to third place game and winners to the final', function () {
    $c = startedChampionship();
    simulate($c, new FakeScoreGenerator([[2, 1]]));  // QF: vencem 1,3,5,7
    simulate($c, new FakeScoreGenerator([[0, 3]]));  // SF: vencem os away (3 e 7)

    $third = $c->gamesOfPhase(Phase::ThirdPlace)[0];
    $final = $c->gamesOfPhase(Phase::Final)[0];

    expect($c->status())->toBe(ChampionshipStatus::Finals)
        ->and([$third->home->teamId, $third->away->teamId])->toBe([1, 5])
        ->and([$final->home->teamId, $final->away->teamId])->toBe([3, 7]);
});

test('finishing the championship records 1st to 4th places', function () {
    $c = startedChampionship();
    simulate($c, new FakeScoreGenerator([[2, 1]]));
    simulate($c, new FakeScoreGenerator([[0, 3]]));
    simulate($c, new FakeScoreGenerator([[1, 0]]));  // 3º lugar: home (1); final: home (3)

    expect($c->status())->toBe(ChampionshipStatus::Finished)
        ->and($c->finalStandings())->toBe([1 => 3, 2 => 7, 3 => 1, 4 => 5]);
});

test('a drawn final is decided by accumulated points', function () {
    $c = startedChampionship();
    // QF: casas vencem por margens diferentes → pontuações distintas
    simulate($c, new FakeScoreGenerator([[3, 0], [1, 0], [2, 0], [1, 0]]));
    // SF: away vence os dois jogos com placares distintos
    simulate($c, new FakeScoreGenerator([[0, 1], [0, 2]]));
    // Finals: ambos os jogos empatam 1x1 → decide por pontos
    simulate($c, new FakeScoreGenerator([[1, 1]]));

    $final = $c->gamesOfPhase(Phase::Final)[0];
    expect($final->decidedBy())->toBe(DecidedBy::Points)
        ->and($c->status())->toBe(ChampionshipStatus::Finished);
});

test('cannot simulate a finished championship', function () {
    $c = startedChampionship();
    simulate($c, new FakeScoreGenerator([[2, 1]]));
    simulate($c, new FakeScoreGenerator([[2, 1]]));
    simulate($c, new FakeScoreGenerator([[2, 1]]));
    simulate($c, new FakeScoreGenerator([[2, 1]]));
})->throws(ChampionshipRuleViolation::class);

test('cannot simulate a draft championship', function () {
    simulate(draft(), new FakeScoreGenerator([[1, 0]]));
})->throws(ChampionshipRuleViolation::class);
