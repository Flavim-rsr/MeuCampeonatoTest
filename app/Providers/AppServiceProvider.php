<?php

namespace App\Providers;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Events\ChampionshipFinished;
use App\Infrastructure\Persistence\EloquentChampionshipRepository;
use App\Infrastructure\Random\RandomShuffler;
use App\Infrastructure\Score\PythonScoreGenerator;
use App\Listeners\UpdateTeamStatistics;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChampionshipRepositoryInterface::class, EloquentChampionshipRepository::class);
        $this->app->bind(ShufflerInterface::class, RandomShuffler::class);

        $this->app->singleton(ScoreGeneratorInterface::class, fn () => new PythonScoreGenerator(
            config('simulation.python_binary'),
            config('simulation.script_path'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registered explicitly (rather than relying on Laravel's listener
        // auto-discovery) so the wiring is visible here alongside the rest
        // of the app's bindings. Runs synchronously (no ShouldQueue) since
        // callers must see the updated read model immediately.
        Event::listen(ChampionshipFinished::class, UpdateTeamStatistics::class);
    }
}
