<?php

use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('championship persists with teams in registration order', function () {
    $user = User::factory()->create();
    $championship = Championship::factory()->for($user)->create();
    $teams = Team::factory()->count(3)->for($user)->create();

    foreach ($teams as $i => $team) {
        $championship->teams()->attach($team, ['registration_order' => $i + 1]);
    }

    expect($championship->fresh()->teams->pluck('id')->all())->toBe($teams->pluck('id')->all())
        ->and($championship->status->value)->toBe('draft');
});
