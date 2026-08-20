<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChampionshipController;
use App\Http\Controllers\Api\EnrollTeamsController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\SimulateChampionshipController;
use App\Http\Controllers\Api\SimulatePhaseController;
use App\Http\Controllers\Api\StartChampionshipController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function (): void {
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:api')->group(function (): void {
        Route::get('teams', [TeamController::class, 'index']);
        Route::post('teams', [TeamController::class, 'store']);
        Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');

        Route::get('championships', [ChampionshipController::class, 'index'])->name('championships.index');
        Route::post('championships', [ChampionshipController::class, 'store'])->name('championships.store');
        Route::get('championships/{championship}', [ChampionshipController::class, 'show'])->name('championships.show');
        Route::post('championships/{championship}/teams', EnrollTeamsController::class)->name('championships.teams.enroll');
        Route::post('championships/{championship}/start', StartChampionshipController::class)->name('championships.start');
        Route::post('championships/{championship}/phases/simulate', SimulatePhaseController::class)->name('championships.phases.simulate');
        Route::post('championships/{championship}/simulate', SimulateChampionshipController::class)->name('championships.simulate');

        Route::get('rankings', RankingController::class)->name('rankings.index');
    });
});
