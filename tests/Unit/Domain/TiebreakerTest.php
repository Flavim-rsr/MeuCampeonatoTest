<?php

use App\Domain\Tiebreaker\TiebreakContext;
use App\Domain\Tiebreaker\TiebreakerChainFactory;
use App\Domain\Tournament\DecidedBy;
use App\Domain\Tournament\TeamEntry;
use App\Domain\Tournament\TiebreakerMode;
use Tests\Doubles\FakeScoreGenerator;

function ctx(array $standings): TiebreakContext
{
    return new TiebreakContext(new TeamEntry(1, 'A', 2), new TeamEntry(2, 'B', 1), $standings);
}

test('higher accumulated points wins the tiebreak', function () {
    $chain = TiebreakerChainFactory::forMode(TiebreakerMode::Standard, new FakeScoreGenerator([[1, 0]]));
    $decision = $chain->resolve(ctx([1 => 3, 2 => 1]));

    expect($decision->winnerTeamId)->toBe(1)->and($decision->decidedBy)->toBe(DecidedBy::Points);
});

test('equal points falls back to registration order in standard mode', function () {
    $chain = TiebreakerChainFactory::forMode(TiebreakerMode::Standard, new FakeScoreGenerator([[1, 0]]));
    $decision = $chain->resolve(ctx([1 => 2, 2 => 2]));

    expect($decision->winnerTeamId)->toBe(2)  // B inscrito primeiro (order 1)
        ->and($decision->decidedBy)->toBe(DecidedBy::RegistrationOrder);
});

test('penalties mode shoots out before registration order', function () {
    $chain = TiebreakerChainFactory::forMode(TiebreakerMode::Penalties, new FakeScoreGenerator([[4, 2]]));
    $decision = $chain->resolve(ctx([1 => 0, 2 => 0]));

    expect($decision->winnerTeamId)->toBe(1)
        ->and($decision->decidedBy)->toBe(DecidedBy::Penalties)
        ->and($decision->penalties->home)->toBe(4);
});

test('drawn shootouts are retried (sudden death)', function () {
    $chain = TiebreakerChainFactory::forMode(TiebreakerMode::Penalties, new FakeScoreGenerator([[3, 3], [3, 3], [2, 5]]));
    $decision = $chain->resolve(ctx([1 => 0, 2 => 0]));

    expect($decision->winnerTeamId)->toBe(2)->and($decision->decidedBy)->toBe(DecidedBy::Penalties);
});

test('five drawn shootouts fall back to registration order', function () {
    $chain = TiebreakerChainFactory::forMode(TiebreakerMode::Penalties, new FakeScoreGenerator([[3, 3]]));
    $decision = $chain->resolve(ctx([1 => 0, 2 => 0]));

    expect($decision->decidedBy)->toBe(DecidedBy::RegistrationOrder);
});
