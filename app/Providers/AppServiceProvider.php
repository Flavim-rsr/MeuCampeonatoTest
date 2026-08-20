<?php

namespace App\Providers;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Contracts\ShufflerInterface;
use App\Infrastructure\Persistence\EloquentChampionshipRepository;
use App\Infrastructure\Random\RandomShuffler;
use App\Infrastructure\Score\PythonScoreGenerator;
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
        //
    }
}
