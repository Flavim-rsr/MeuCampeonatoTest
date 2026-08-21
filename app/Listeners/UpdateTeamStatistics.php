<?php

namespace App\Listeners;

use App\Domain\Events\ChampionshipFinished;
use App\Models\Championship;
use App\Models\Game;
use App\Models\Team;
use App\Models\TeamStatistic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;

class UpdateTeamStatistics implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 5;

    /**
     * Update the historical team_statistics read model when a championship
     * finishes: every enrolled team gets +1 championships_played and its
     * goals for/against tallied from this championship's games, while the
     * top three placed teams get +1 titles/runner_ups/third_places.
     */
    public function handle(ChampionshipFinished $event): void
    {
        $championship = Championship::query()
            ->with('teams', 'games')
            ->findOrFail($event->championshipId);

        $goalsByTeam = $this->goalsByTeam($championship->games);

        foreach ($championship->teams as $team) {
            /** @var Team $team */
            $statistic = TeamStatistic::query()->firstOrNew(['team_id' => $team->id]);

            $statistic->championships_played++;

            $goals = $goalsByTeam[$team->id] ?? ['for' => 0, 'against' => 0];
            $statistic->goals_for += $goals['for'];
            $statistic->goals_against += $goals['against'];

            $position = array_search($team->id, $event->finalStandings, true);

            match ($position) {
                1 => $statistic->titles++,
                2 => $statistic->runner_ups++,
                3 => $statistic->third_places++,
                default => null,
            };

            $statistic->save();
        }
    }

    /**
     * Sum home/away goals for every team across the championship's games.
     * Penalty goals never count.
     *
     * @param  Collection<int, Game>  $games
     * @return array<int, array{for: int, against: int}>
     */
    private function goalsByTeam(Collection $games): array
    {
        $goals = [];

        foreach ($games as $game) {
            if ($game->home_score === null || $game->away_score === null) {
                continue;
            }

            $goals[$game->home_team_id] ??= ['for' => 0, 'against' => 0];
            $goals[$game->away_team_id] ??= ['for' => 0, 'against' => 0];

            $goals[$game->home_team_id]['for'] += $game->home_score;
            $goals[$game->home_team_id]['against'] += $game->away_score;

            $goals[$game->away_team_id]['for'] += $game->away_score;
            $goals[$game->away_team_id]['against'] += $game->home_score;
        }

        return $goals;
    }
}
