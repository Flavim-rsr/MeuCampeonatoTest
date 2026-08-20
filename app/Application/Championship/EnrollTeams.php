<?php

namespace App\Application\Championship;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Models\Team;
use Illuminate\Database\DatabaseManager;

final class EnrollTeams
{
    public function __construct(
        private readonly ChampionshipRepositoryInterface $repository,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @param  array<int, int>  $teamIds
     */
    public function handle(int $championshipId, array $teamIds): void
    {
        $this->db->transaction(function () use ($championshipId, $teamIds): void {
            $aggregate = $this->repository->find($championshipId);

            $teams = Team::query()->findMany($teamIds)->keyBy('id');

            foreach ($teamIds as $teamId) {
                /** @var Team $team */
                $team = $teams->get($teamId);

                $aggregate->enroll($teamId, $team->name);
            }

            $this->repository->save($aggregate);
        });
    }
}
