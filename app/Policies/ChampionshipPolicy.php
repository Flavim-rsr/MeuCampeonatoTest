<?php

namespace App\Policies;

use App\Models\Championship;
use App\Models\User;

class ChampionshipPolicy
{
    /**
     * Determine whether the user can view the championship.
     */
    public function view(User $user, Championship $championship): bool
    {
        return $championship->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the championship
     * (enroll teams, start the tournament, etc).
     */
    public function update(User $user, Championship $championship): bool
    {
        return $championship->user_id === $user->id;
    }
}
