<?php

use App\Domain\Exceptions\ChampionshipRuleViolation;
use App\Domain\Exceptions\ScoreGenerationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Listeners are wired explicitly via Event::listen (see
    // AppServiceProvider::boot) rather than relying on Laravel's
    // handle()-signature auto-discovery, so discovery is disabled here to
    // avoid registering (and firing) the same listener twice.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        // This is an API-only app: there is no "login" route to redirect
        // guests to, so unauthenticated requests must never attempt one
        // (Laravel's default redirectGuestsTo(fn () => route('login'))
        // throws RouteNotFoundException for clients that omit an
        // Accept: application/json header).
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (ChampionshipRuleViolation $e) => response()->json([
            'message' => $e->getMessage(),
        ], 409));

        $exceptions->render(fn (ScoreGenerationException $e) => response()->json([
            'message' => 'Score prediction service failed: '.$e->getMessage(),
        ], 502));
    })->create();
