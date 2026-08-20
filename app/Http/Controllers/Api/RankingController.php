<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RankingResource;
use App\Models\TeamStatistic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    /**
     * The authenticated user's teams with recorded statistics, ordered by
     * titles, runner-ups, third places and goal difference (all desc).
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $statistics = TeamStatistic::query()
            ->with('team')
            ->whereHas('team', fn ($query) => $query->where('user_id', $request->user()->getAuthIdentifier()))
            ->orderByDesc('titles')
            ->orderByDesc('runner_ups')
            ->orderByDesc('third_places')
            ->orderByDesc(DB::raw('CAST(goals_for AS SIGNED) - CAST(goals_against AS SIGNED)'))
            ->get();

        return RankingResource::collection($statistics);
    }
}
