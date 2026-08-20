<?php

use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Tournament\Championship;
use App\Domain\Tournament\TiebreakerMode;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Domain Test Helpers
|--------------------------------------------------------------------------
|
| Shared helpers for the Championship domain tests.
|
*/

function draft(): Championship
{
    return new Championship(null, 'Copa do Bairro', TiebreakerMode::Standard);
}

function identityShuffler(): ShufflerInterface
{
    return new class implements ShufflerInterface
    {
        public function shuffle(array $items): array
        {
            return array_values($items);
        }
    };
}

function enrollMany(Championship $c, int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        $c->enroll($i, "Team {$i}");
    }
}

/**
 * Build the Authorization header for an authenticated API request.
 *
 * @return array<string, string>
 */
function actingAsApi(User $user): array
{
    return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
}
