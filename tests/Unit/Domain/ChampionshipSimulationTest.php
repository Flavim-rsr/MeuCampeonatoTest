<?php

use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Exceptions\ChampionshipRuleViolation;
use App\Domain\Scoring\PenaltyShootout;
use App\Domain\Scoring\StandardScoringPolicy;
use App\Domain\Scoring\StandingsCalculator;
use App\Domain\Tiebreaker\TiebreakerChainFactory;
use App\Domain\Tournament\Championship;
use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\DecidedBy;
use App\Domain\Tournament\Phase;
use App\Domain\Tournament\TiebreakerMode;
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

test('semi finals are re-drawn among quarter-final winners, not bracket-fixed', function () {
    $reversingShuffler = new class implements ShufflerInterface
    {
        public function shuffle(array $items): array
        {
            return array_reverse(array_values($items));
        }
    };

    $c = draft();
    enrollMany($c, 8);
    $c->start($reversingShuffler);

    // start() pairs $shuffler->shuffle($teams) sequentially. Teams are enrolled
    // 1..8, so reversing gives [8,7,6,5,4,3,2,1], paired as:
    //   QF pos1: 8 v 7   QF pos2: 6 v 5   QF pos3: 4 v 3   QF pos4: 2 v 1
    $scores = new FakeScoreGenerator([[2, 1]]); // home always wins
    $chain = TiebreakerChainFactory::forMode($c->tiebreakerMode, $scores);
    $standings = new StandingsCalculator(new StandardScoringPolicy);

    $c->simulateCurrentPhase($scores, $chain, $standings, $reversingShuffler);

    // Home always wins => QF winners in position order: 8, 6, 4, 2.
    // drawSemiFinals() shuffles that winners list with the same reversing
    // shuffler: reverse([8,6,4,2]) = [2,4,6,8], paired sequentially:
    //   SF pos1: 2 v 4   SF pos2: 6 v 8
    // A bracket-fixed (non-reshuffling) implementation would instead pair the
    // winners list as-is: SF pos1: 8 v 6, SF pos2: 4 v 2 — different from below.
    $sf = $c->gamesOfPhase(Phase::SemiFinals);

    expect($c->status())->toBe(ChampionshipStatus::SemiFinals)
        ->and($sf)->toHaveCount(2)
        ->and([$sf[0]->home->teamId, $sf[0]->away->teamId])->toBe([2, 4])
        ->and([$sf[1]->home->teamId, $sf[1]->away->teamId])->toBe([6, 8]);
});

test('penalties tiebreaker mode resolves quarter-final draws by shootout without affecting standings', function () {
    $c = new Championship(null, 'Copa do Bairro', TiebreakerMode::Penalties);
    enrollMany($c, 8);
    $c->start(identityShuffler());

    // QF pairing (identity shuffler): pos1 1v2, pos2 3v4, pos3 5v6, pos4 7v8.
    // Cyclic pairs [[1,1],[4,2]] feed BOTH recordScore() and the
    // PenaltyShootoutTiebreaker (same generator instance). Per game:
    //   call N   (index N%2=0) -> [1,1] recordScore: draw.
    //   call N+1 (index N%2=1) -> [4,2] shootout attempt: decisive, home wins.
    // PointsTiebreaker ties 0-0 for every game (only 1-1 draws recorded so far),
    // so PenaltyShootoutTiebreaker always resolves it on the first attempt.
    $scores = new FakeScoreGenerator([[1, 1], [4, 2]]);
    $chain = TiebreakerChainFactory::forMode($c->tiebreakerMode, $scores);
    $standings = new StandingsCalculator(new StandardScoringPolicy);

    $c->simulateCurrentPhase($scores, $chain, $standings, identityShuffler());

    $games = $c->gamesOfPhase(Phase::QuarterFinals);

    foreach ($games as $game) {
        expect($game->decidedBy())->toBe(DecidedBy::Penalties)
            ->and($game->penalties())->toBeInstanceOf(PenaltyShootout::class)
            ->and($game->penalties()->home)->toBe(4)
            ->and($game->penalties()->away)->toBe(2);
    }

    expect([
        $games[0]->winnerTeamId(),
        $games[1]->winnerTeamId(),
        $games[2]->winnerTeamId(),
        $games[3]->winnerTeamId(),
    ])->toBe([1, 3, 5, 7]); // home team wins every shootout (4-2)

    // Penalty goals must never enter accumulated standings: every recorded
    // score was a 1-1 draw, so every team's net points must be 0.
    $accumulated = $standings->calculate($c->games());
    foreach ($c->teams() as $team) {
        expect($accumulated[$team->teamId] ?? 0)->toBe(0);
    }
});
