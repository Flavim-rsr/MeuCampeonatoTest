<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $championship_id
 * @property int $team_id
 * @property int $registration_order
 */
class ChampionshipTeamPivot extends Pivot
{
    protected $table = 'championship_team';
}
