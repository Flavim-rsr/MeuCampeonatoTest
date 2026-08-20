<?php

namespace App\Domain\Contracts;

use App\Domain\Tournament\Championship;

interface ChampionshipRepositoryInterface
{
    /**
     * Reconstitute the Championship aggregate from persistence.
     *
     * @throws \RuntimeException when no championship exists for the given id.
     */
    public function find(int $id): Championship;

    /**
     * Persist the aggregate's state: status, final standings, team enrollment and games.
     */
    public function save(Championship $championship): void;

    /**
     * Accumulated points standings for a championship, computed via SQL.
     *
     * @return array<int, array{team_id: int, name: string, registration_order: int, points: int}>
     */
    public function standings(int $championshipId): array;
}
