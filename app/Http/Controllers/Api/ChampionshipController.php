<?php

namespace App\Http\Controllers\Api;

use App\Application\Championship\CreateChampionship;
use App\Domain\Tournament\TiebreakerMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Championship\StoreChampionshipRequest;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ChampionshipController extends Controller
{
    public function __construct(
        private readonly CreateChampionship $createChampionship,
    ) {}

    /**
     * List the authenticated user's championships, optionally filtered by status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $championships = Championship::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->with('teams')
            ->when(
                $request->query('status'),
                fn ($query, $status) => $query->where('status', $status),
            )
            ->get();

        return ChampionshipResource::collection($championships);
    }

    /**
     * Create a new championship, in draft status, owned by the authenticated user.
     */
    public function store(StoreChampionshipRequest $request): JsonResponse
    {
        $championship = $this->createChampionship->handle(
            $request->user()->getAuthIdentifier(),
            $request->validated('name'),
            TiebreakerMode::from($request->validated('tiebreaker_mode')),
        );

        $championship->load('teams');

        return (new ChampionshipResource($championship))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('championships.show', $championship));
    }

    /**
     * Show a single championship owned by the authenticated user.
     */
    public function show(Championship $championship): ChampionshipResource
    {
        Gate::authorize('view', $championship);

        $championship->load(['teams', 'games.homeTeam', 'games.awayTeam']);

        return new ChampionshipResource($championship);
    }
}
