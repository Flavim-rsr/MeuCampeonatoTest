<?php

use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function userWithTeams(int $count = 8): array
{
    $user = User::factory()->create();

    return [$user, Team::factory()->count($count)->for($user)->create()];
}

test('creates a championship in draft', function () {
    [$user] = userWithTeams(0);

    $this->postJson('/api/v1/championships', ['name' => 'Copa 2026'], actingAsApi($user))
        ->assertCreated()->assertHeader('Location')
        ->assertJsonPath('data.status', 'draft');
});

test('enrolls eight teams preserving order', function () {
    [$user, $teams] = userWithTeams();
    $championship = Championship::factory()->for($user)->create();

    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => $teams->pluck('id')->all()], actingAsApi($user))
        ->assertOk()
        ->assertJsonPath('data.teams.0.registration_order', 1)
        ->assertJsonPath('data.teams.7.registration_order', 8);
});

test('rejects a ninth team with 409', function () {
    [$user, $teams] = userWithTeams(9);
    $championship = Championship::factory()->for($user)->create();
    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => $teams->take(8)->pluck('id')->all()], actingAsApi($user));

    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => [$teams->last()->id]], actingAsApi($user))
        ->assertConflict();
});

test('start requires exactly eight teams', function () {
    [$user, $teams] = userWithTeams(5);
    $championship = Championship::factory()->for($user)->create();
    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => $teams->pluck('id')->all()], actingAsApi($user));

    $this->postJson("/api/v1/championships/{$championship->id}/start", [], actingAsApi($user))
        ->assertConflict();
});

test('start draws the quarter finals', function () {
    [$user, $teams] = userWithTeams();
    $championship = Championship::factory()->for($user)->create();
    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => $teams->pluck('id')->all()], actingAsApi($user));

    $this->postJson("/api/v1/championships/{$championship->id}/start", [], actingAsApi($user))
        ->assertOk()->assertJsonPath('data.status', 'quarter_finals')
        ->assertJsonCount(4, 'data.games');
});

test('cannot see or act on another users championship', function () {
    [$owner] = userWithTeams(0);
    $championship = Championship::factory()->for($owner)->create();
    [$intruder] = userWithTeams(0);

    $this->getJson("/api/v1/championships/{$championship->id}", actingAsApi($intruder))->assertForbidden();
    $this->postJson("/api/v1/championships/{$championship->id}/start", [], actingAsApi($intruder))->assertForbidden();
});

test('lists my championships with status filter', function () {
    [$user] = userWithTeams(0);
    Championship::factory()->for($user)->count(2)->create();
    Championship::factory()->for($user)->create(['status' => 'finished']);

    $this->getJson('/api/v1/championships?status=finished', actingAsApi($user))
        ->assertOk()->assertJsonCount(1, 'data');
});

test('rejects a foreign users team id when enrolling with 422', function () {
    [$user] = userWithTeams(0);
    [, $foreignTeams] = userWithTeams(1);
    $championship = Championship::factory()->for($user)->create();

    $this->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => [$foreignTeams->first()->id]], actingAsApi($user))
        ->assertUnprocessable();
});
