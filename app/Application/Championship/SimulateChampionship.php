<?php

namespace App\Application\Championship;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Exceptions\ChampionshipRuleViolation;
use App\Domain\Tournament\ChampionshipStatus;

final class SimulateChampionship
{
    public function __construct(
        private readonly ChampionshipRepositoryInterface $repository,
        private readonly SimulatePhase $simulatePhase,
    ) {}

    public function handle(int $championshipId): void
    {
        $status = $this->repository->find($championshipId)->status();

        if ($status === ChampionshipStatus::Draft || $status === ChampionshipStatus::Finished) {
            throw ChampionshipRuleViolation::invalidTransition($status->value);
        }

        while ($status !== ChampionshipStatus::Finished) {
            $this->simulatePhase->handle($championshipId);

            $status = $this->repository->find($championshipId)->status();
        }
    }
}
