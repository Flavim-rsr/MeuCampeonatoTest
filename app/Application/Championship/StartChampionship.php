<?php

namespace App\Application\Championship;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Contracts\ShufflerInterface;
use Illuminate\Database\DatabaseManager;

final class StartChampionship
{
    public function __construct(
        private readonly ChampionshipRepositoryInterface $repository,
        private readonly ShufflerInterface $shuffler,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(int $championshipId): void
    {
        $this->db->transaction(function () use ($championshipId): void {
            $aggregate = $this->repository->find($championshipId);

            $aggregate->start($this->shuffler);

            $this->repository->save($aggregate);
        });
    }
}
