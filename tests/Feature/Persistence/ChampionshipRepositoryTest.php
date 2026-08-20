<?php

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Tournament\ChampionshipStatus;
use App\Models\Championship as ChampionshipModel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function repoWithStartedChampionship(): array
{
    $user = User::factory()->create();
    $model = ChampionshipModel::factory()->for($user)->create();
    $teams = Team::factory()->count(8)->for($user)->create();
    $repo = app(ChampionshipRepositoryInterface::class);

    $aggregate = $repo->find($model->id);
    foreach ($teams as $team) {
        $aggregate->enroll($team->id, $team->name);
    }
    $aggregate->start(identityShuffler());
    $repo->save($aggregate);

    return [$repo, $model->id];
}

test('round-trips an aggregate through persistence', function () {
    [$repo, $id] = repoWithStartedChampionship();

    $reloaded = $repo->find($id);

    expect($reloaded->status())->toBe(ChampionshipStatus::QuarterFinals)
        ->and($reloaded->teams())->toHaveCount(8)
        ->and($reloaded->games())->toHaveCount(4);
});

test('standings query returns zeroed points before any game', function () {
    [$repo, $id] = repoWithStartedChampionship();

    $standings = $repo->standings($id);

    expect($standings)->toHaveCount(8)
        ->and(collect($standings)->pluck('points')->unique()->all())->toBe([0]);
});
