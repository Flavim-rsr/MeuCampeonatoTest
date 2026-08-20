<?php

namespace App\Models;

use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\TiebreakerMode;
use Database\Factories\ChampionshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property ChampionshipStatus $status
 * @property TiebreakerMode $tiebreaker_mode
 * @property string $scoring_mode
 * @property int|null $first_place_team_id
 * @property int|null $second_place_team_id
 * @property int|null $third_place_team_id
 * @property int|null $fourth_place_team_id
 */
#[Fillable([
    'user_id',
    'name',
    'status',
    'tiebreaker_mode',
    'scoring_mode',
    'first_place_team_id',
    'second_place_team_id',
    'third_place_team_id',
    'fourth_place_team_id',
])]
class Championship extends Model
{
    /** @use HasFactory<ChampionshipFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChampionshipStatus::class,
            'tiebreaker_mode' => TiebreakerMode::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Team, $this, ChampionshipTeamPivot>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(ChampionshipTeamPivot::class)
            ->withPivot('registration_order')
            ->orderByPivot('registration_order');
    }

    /**
     * Games ordered by phase then position within phase.
     * Note: string ordering on `phase` is a stable but non-chronological
     * order (quarter_finals, semi_finals, third_place, final alphabetically
     * differs from tournament progression); acceptable per spec.
     *
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class)->orderBy('phase')->orderBy('position');
    }
}
