<?php

use App\Application\Championship\EnrollTeams;
use App\Application\Championship\SimulateChampionship;
use App\Application\Championship\StartChampionship;
use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Events\ChampionshipFinished;
use App\Models\Championship as ChampionshipModel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Doubles\FakeScoreGenerator;

uses(RefreshDatabase::class);

test('simulates a full championship and dispatches the finished event', function () {
    Event::fake([ChampionshipFinished::class]);
    app()->instance(ScoreGeneratorInterface::class, new FakeScoreGenerator([[2, 1], [1, 3], [0, 2]]));

    $user = User::factory()->create();
    $model = ChampionshipModel::factory()->for($user)->create();
    $teams = Team::factory()->count(8)->for($user)->create();

    app(EnrollTeams::class)->handle($model->id, $teams->pluck('id')->all());
    app(StartChampionship::class)->handle($model->id);
    app(SimulateChampionship::class)->handle($model->id);

    $model->refresh();
    expect($model->status->value)->toBe('finished')
        ->and($model->first_place_team_id)->not->toBeNull()
        ->and($model->games()->whereNull('winner_team_id')->count())->toBe(0)
        ->and($model->games()->count())->toBe(8);
    Event::assertDispatched(ChampionshipFinished::class);
});
