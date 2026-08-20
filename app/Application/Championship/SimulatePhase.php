<?php

namespace App\Application\Championship;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Events\ChampionshipFinished;
use App\Domain\Scoring\StandardScoringPolicy;
use App\Domain\Scoring\StandingsCalculator;
use App\Domain\Tiebreaker\TiebreakerChainFactory;
use App\Domain\Tournament\Championship as ChampionshipAggregate;
use App\Domain\Tournament\ChampionshipStatus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;

final class SimulatePhase
{
    public function __construct(
        private readonly ChampionshipRepositoryInterface $repository,
        private readonly ScoreGeneratorInterface $scores,
        private readonly ShufflerInterface $shuffler,
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
    ) {}

    public function handle(int $championshipId): void
    {
        /** @var ChampionshipAggregate $aggregate */
        $aggregate = $this->db->transaction(function () use ($championshipId): ChampionshipAggregate {
            $aggregate = $this->repository->find($championshipId);

            $chain = TiebreakerChainFactory::forMode($aggregate->tiebreakerMode, $this->scores);
            $standings = new StandingsCalculator(new StandardScoringPolicy);

            $aggregate->simulateCurrentPhase($this->scores, $chain, $standings, $this->shuffler);

            $this->repository->save($aggregate);

            return $aggregate;
        });

        if ($aggregate->status() === ChampionshipStatus::Finished) {
            $this->events->dispatch(new ChampionshipFinished($championshipId, $aggregate->finalStandings()));
        }
    }
}
