<?php

namespace App\Models;

use App\Domain\Tournament\DecidedBy;
use App\Domain\Tournament\Phase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $championship_id
 * @property Phase $phase
 * @property int $position
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int|null $penalty_home
 * @property int|null $penalty_away
 * @property int|null $winner_team_id
 * @property DecidedBy|null $decided_by
 */
#[Fillable([
    'championship_id',
    'phase',
    'position',
    'home_team_id',
    'away_team_id',
    'home_score',
    'away_score',
    'penalty_home',
    'penalty_away',
    'winner_team_id',
    'decided_by',
])]
class Game extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => Phase::class,
            'decided_by' => DecidedBy::class,
        ];
    }

    /**
     * @return BelongsTo<Championship, $this>
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }
}
