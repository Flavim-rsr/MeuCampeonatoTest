<?php

namespace App\Http\Resources;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Game */
class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase' => $this->phase->value,
            'position' => $this->position,
            'home_team' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
            ],
            'away_team' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
            ],
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
            'penalty_home' => $this->penalty_home,
            'penalty_away' => $this->penalty_away,
            'winner_team_id' => $this->winner_team_id,
            'decided_by' => $this->decided_by?->value,
        ];
    }
}
