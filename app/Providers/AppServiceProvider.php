<?php

namespace App\Providers;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Contracts\ShufflerInterface;
use App\Infrastructure\Persistence\EloquentChampionshipRepository;
use App\Infrastructure\Random\RandomShuffler;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
