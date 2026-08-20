<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    /**
     * List the authenticated user's teams.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->get();

        return TeamResource::collection($teams);
    }

    /**
     * Create a new team owned by the authenticated user.
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::create([
            'user_id' => $request->user()->getAuthIdentifier(),
            'name' => $request->validated('name'),
        ]);

        return (new TeamResource($team))
            ->response()
            ->setStatusCode(201);
    }
}
