<?php

namespace App\Http\Controllers\Api;

use App\Application\Championship\SimulatePhase;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use Illuminate\Support\Facades\Gate;

class SimulatePhaseController extends Controller
{
    public function __construct(
        private readonly SimulatePhase $simulatePhase,
    ) {}

    public function __invoke(Championship $championship): ChampionshipResource
    {
        Gate::authorize('update', $championship);

        $this->simulatePhase->handle($championship->id);

        $championship->refresh()->load(['teams', 'games.homeTeam', 'games.awayTeam']);

        return new ChampionshipResource($championship);
    }
}
