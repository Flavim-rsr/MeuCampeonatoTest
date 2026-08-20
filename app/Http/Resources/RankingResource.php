<?php

namespace App\Http\Resources;

use App\Models\TeamStatistic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TeamStatistic */
class RankingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'team' => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ],
            'championships_played' => $this->championships_played,
            'titles' => $this->titles,
            'runner_ups' => $this->runner_ups,
            'third_places' => $this->third_places,
            'goals_for' => $this->goals_for,
            'goals_against' => $this->goals_against,
            'goal_difference' => $this->goals_for - $this->goals_against,
        ];
    }
}
