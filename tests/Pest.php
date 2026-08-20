<?php

use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Tournament\Championship;
use App\Domain\Tournament\TiebreakerMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

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

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
