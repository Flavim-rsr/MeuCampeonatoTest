<?php

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\JWT;
use Tests\Doubles\FakeScoreGenerator;

uses(RefreshDatabase::class);

function startedViaApi(): array
{
    $user = User::factory()->create();
    $teams = Team::factory()->count(8)->for($user)->create();
    $championship = Championship::factory()->for($user)->create();
    test()->postJson("/api/v1/championships/{$championship->id}/teams",
        ['team_ids' => $teams->pluck('id')->all()], actingAsApi($user));
    test()->postJson("/api/v1/championships/{$championship->id}/start", [], actingAsApi($user));

    return [$user, $championship];
}

beforeEach(fn () => app()->instance(ScoreGeneratorInterface::class, new FakeScoreGenerator([[2, 1], [0, 3], [1, 1], [4, 0]])));

test('simulates one phase and advances the status', function () {
    [$user, $championship] = startedViaApi();

    $this->postJson("/api/v1/championships/{$championship->id}/phases/simulate", [], actingAsApi($user))
        ->assertOk()->assertJsonPath('data.status', 'semi_finals');
});

test('simulates the whole championship to finished with classification', function () {
    [$user, $championship] = startedViaApi();

    $response = $this->postJson("/api/v1/championships/{$championship->id}/simulate", [], actingAsApi($user))
        ->assertOk()->assertJsonPath('data.status', 'finished');

    expect($response->json('data.classification.first.id'))->not->toBeNull()
        ->and(collect($response->json('data.games'))->pluck('decided_by')->filter()->count())->toBe(8);
});

test('simulating a draft championship returns 409', function () {
    $user = User::factory()->create();
    $championship = Championship::factory()->for($user)->create();

    $this->postJson("/api/v1/championships/{$championship->id}/phases/simulate", [], actingAsApi($user))
        ->assertConflict();
});

test('simulating a finished championship returns 409', function () {
    [$user, $championship] = startedViaApi();
    $this->postJson("/api/v1/championships/{$championship->id}/simulate", [], actingAsApi($user));

    $this->postJson("/api/v1/championships/{$championship->id}/simulate", [], actingAsApi($user))
        ->assertConflict();
});

test('drawn games expose how they were decided', function () {
    app()->instance(ScoreGeneratorInterface::class, new FakeScoreGenerator([[1, 1]]));
    [$user, $championship] = startedViaApi();

    $response = $this->postJson("/api/v1/championships/{$championship->id}/phases/simulate", [], actingAsApi($user));

    expect(collect($response->json('data.games'))
        ->where('phase', 'quarter_finals')
        ->pluck('decided_by')
        ->every(fn ($d) => in_array($d, ['points', 'registration_order'])))->toBeTrue();
});

test('another user cannot simulate a foreign championship', function () {
    [, $championship] = startedViaApi();
    $intruder = User::factory()->create();

    // The JWT guard and its underlying token are cached as singletons for
    // the lifetime of the app container, so switching the authenticated
    // user within a single test requires forcing both to re-resolve from
    // the next request's Authorization header.
    app('auth')->forgetGuards();
    app(JWT::class)->unsetToken();

    $this->postJson("/api/v1/championships/{$championship->id}/simulate", [], actingAsApi($intruder))
        ->assertForbidden();
});
