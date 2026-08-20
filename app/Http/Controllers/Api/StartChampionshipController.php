<?php

namespace App\Http\Controllers\Api;

use App\Application\Championship\StartChampionship;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use Illuminate\Support\Facades\Gate;

class StartChampionshipController extends Controller
{
    public function __construct(
        private readonly StartChampionship $startChampionship,
    ) {}

    public function __invoke(Championship $championship): ChampionshipResource
    {
        Gate::authorize('update', $championship);

        $this->startChampionship->handle($championship->id);

        $championship->refresh()->load(['teams', 'games.homeTeam', 'games.awayTeam']);

        return new ChampionshipResource($championship);
    }
}
