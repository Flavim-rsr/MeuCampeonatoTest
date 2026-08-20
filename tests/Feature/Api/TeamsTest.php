<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates a team', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/teams', ['name' => 'Leões da Vila'], actingAsApi($user))
        ->assertCreated()->assertJsonPath('data.name', 'Leões da Vila');
});

test('rejects duplicated team name for the same user', function () {
    $user = User::factory()->create();
    Team::factory()->for($user)->create(['name' => 'Leões']);

    $this->postJson('/api/v1/teams', ['name' => 'Leões'], actingAsApi($user))
        ->assertUnprocessable();
});

test('lists only my teams', function () {
    $mine = User::factory()->create();
    Team::factory()->count(2)->for($mine)->create();
    Team::factory()->count(3)->for(User::factory()->create())->create();

    $this->getJson('/api/v1/teams', actingAsApi($mine))
        ->assertOk()->assertJsonCount(2, 'data');
});
