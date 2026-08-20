<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_id
 * @property int $championships_played
 * @property int $titles
 * @property int $runner_ups
 * @property int $third_places
 * @property int $goals_for
 * @property int $goals_against
 */
#[Fillable([
    'team_id',
    'championships_played',
    'titles',
    'runner_ups',
    'third_places',
    'goals_for',
    'goals_against',
])]
class TeamStatistic extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'championships_played' => 'integer',
            'titles' => 'integer',
            'runner_ups' => 'integer',
            'third_places' => 'integer',
            'goals_for' => 'integer',
            'goals_against' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
