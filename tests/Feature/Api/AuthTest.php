<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registers a user', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Flavio', 'email' => 'f@x.com', 'password' => 'secret123', 'password_confirmation' => 'secret123',
    ])->assertCreated()->assertJsonPath('data.email', 'f@x.com');
});

test('logs in and returns a bearer token', function () {
    User::factory()->create(['email' => 'f@x.com', 'password' => 'secret123']);

    $this->postJson('/api/v1/auth/login', ['email' => 'f@x.com', 'password' => 'secret123'])
        ->assertOk()->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
});

test('rejects wrong credentials', function () {
    User::factory()->create(['email' => 'f@x.com', 'password' => 'secret123']);

    $this->postJson('/api/v1/auth/login', ['email' => 'f@x.com', 'password' => 'wrong'])
        ->assertUnauthorized();
});

test('me returns the authenticated user', function () {
    $user = User::factory()->create();

    $this->getJson('/api/v1/auth/me', actingAsApi($user))
        ->assertOk()->assertJsonPath('data.id', $user->id);
});

test('protected routes reject missing token', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('protected routes reject missing token even without an Accept header', function () {
    // Plain get() (no Accept: application/json header) mimics a real-world
    // client such as curl. This must still return 401 JSON, not a 500 from
    // Laravel trying to redirect a guest to a non-existent "login" route.
    $this->get('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('message', 'Unauthenticated.');
});

test('protected routes reject a garbage bearer token even without an Accept header', function () {
    $this->withHeaders(['Authorization' => 'Bearer garbage'])
        ->get('/api/v1/auth/me')
        ->assertUnauthorized();
});
