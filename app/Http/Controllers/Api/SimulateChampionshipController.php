<?php

namespace App\Http\Controllers\Api;

use App\Application\Championship\SimulateChampionship;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use Illuminate\Support\Facades\Gate;

class SimulateChampionshipController extends Controller
{
    public function __construct(
        private readonly SimulateChampionship $simulateChampionship,
    ) {}

    public function __invoke(Championship $championship): ChampionshipResource
    {
        Gate::authorize('update', $championship);

        $this->simulateChampionship->handle($championship->id);

        $championship->refresh()->load(['teams', 'games.homeTeam', 'games.awayTeam']);

        return new ChampionshipResource($championship);
    }
}
