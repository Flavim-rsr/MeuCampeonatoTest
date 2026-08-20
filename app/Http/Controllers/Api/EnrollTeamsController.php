<?php

namespace App\Http\Controllers\Api;

use App\Application\Championship\EnrollTeams;
use App\Http\Controllers\Controller;
use App\Http\Requests\Championship\EnrollTeamsRequest;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use Illuminate\Support\Facades\Gate;

class EnrollTeamsController extends Controller
{
    public function __construct(
        private readonly EnrollTeams $enrollTeams,
    ) {}

    public function __invoke(EnrollTeamsRequest $request, Championship $championship): ChampionshipResource
    {
        Gate::authorize('update', $championship);

        $this->enrollTeams->handle($championship->id, $request->validated('team_ids'));

        $championship->refresh()->load(['teams', 'games.homeTeam', 'games.awayTeam']);

        return new ChampionshipResource($championship);
    }
}
