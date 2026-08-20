<?php

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Doubles\FakeScoreGenerator;

uses(RefreshDatabase::class);

test('finishing a championship feeds the historical ranking', function () {
    app()->instance(ScoreGeneratorInterface::class, new FakeScoreGenerator([[2, 0], [1, 3]]));
    $user = User::factory()->create();
    $teams = Team::factory()->count(8)->for($user)->create();
    $championship = Championship::factory()->for($user)->create();
    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => $teams->pluck('id')->all()], actingAsApi($user));
    $this->postJson("/api/v1/championships/{$championship->id}/start", [], actingAsApi($user));
    $this->postJson("/api/v1/championships/{$championship->id}/simulate", [], actingAsApi($user));

    $response = $this->getJson('/api/v1/rankings', actingAsApi($user))->assertOk();

    expect($response->json('data'))->toHaveCount(8)
        ->and(collect($response->json('data'))->sum('titles'))->toBe(1)
        ->and(collect($response->json('data'))->sum('championships_played'))->toBe(8)
        ->and($response->json('data.0.titles'))->toBe(1);
});
