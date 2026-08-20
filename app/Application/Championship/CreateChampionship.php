<?php

namespace App\Application\Championship;

use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\TiebreakerMode;
use App\Models\Championship as ChampionshipModel;

final class CreateChampionship
{
    public function handle(int $userId, string $name, TiebreakerMode $mode): ChampionshipModel
    {
        return ChampionshipModel::query()->create([
            'user_id' => $userId,
            'name' => $name,
            'status' => ChampionshipStatus::Draft,
            'tiebreaker_mode' => $mode,
        ]);
    }
}
