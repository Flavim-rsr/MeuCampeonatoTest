<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Scoring\PenaltyShootout;
use App\Domain\Scoring\Score;
use App\Domain\Tournament\Championship as ChampionshipAggregate;
use App\Domain\Tournament\Game as GameAggregate;
use App\Domain\Tournament\TeamEntry;
use App\Models\Championship as ChampionshipModel;
use App\Models\Game as GameModel;
use Illuminate\Support\Facades\DB;

final class EloquentChampionshipRepository implements ChampionshipRepositoryInterface
{
    public function find(int $id): ChampionshipAggregate
    {
        $model = ChampionshipModel::query()->with(['teams', 'games'])->findOrFail($id);

        /** @var array<int, TeamEntry> $teamEntries */
        $teamEntries = [];
        foreach ($model->teams as $team) {
            $teamEntries[$team->id] = new TeamEntry(
                $team->id,
                $team->name,
                (int) $team->pivot->registration_order,
            );
        }

        $games = [];
        foreach ($model->games as $game) {
            $home = $teamEntries[$game->home_team_id]
                ?? throw new \RuntimeException("Missing team entry for game home team {$game->home_team_id}.");
            $away = $teamEntries[$game->away_team_id]
                ?? throw new \RuntimeException("Missing team entry for game away team {$game->away_team_id}.");

            $domainGame = new GameAggregate($game->phase, $game->position, $home, $away);

            if ($game->home_score !== null && $game->away_score !== null) {
                $domainGame->recordScore(new Score($game->home_score, $game->away_score));
            }

            if ($game->winner_team_id !== null && $game->decided_by !== null) {
                $penalties = ($game->penalty_home !== null && $game->penalty_away !== null)
                    ? new PenaltyShootout($game->penalty_home, $game->penalty_away)
                    : null;

                $domainGame->decide($game->winner_team_id, $game->decided_by, $penalties);
            }

            $games[] = $domainGame;
        }

        $finalStandings = [];
        foreach ([
            1 => $model->first_place_team_id,
            2 => $model->second_place_team_id,
            3 => $model->third_place_team_id,
            4 => $model->fourth_place_team_id,
        ] as $place => $teamId) {
            if ($teamId !== null) {
                $finalStandings[$place] = $teamId;
            }
        }

        return new ChampionshipAggregate(
            $model->id,
            $model->name,
            $model->tiebreaker_mode,
            $model->status,
            array_values($teamEntries),
            $games,
            $finalStandings,
        );
    }

    public function save(ChampionshipAggregate $championship): void
    {
        if ($championship->id === null) {
            throw new \LogicException('Cannot save a championship without an id.');
        }

        $model = ChampionshipModel::query()->findOrFail($championship->id);

        $finalStandings = $championship->finalStandings();

        $model->status = $championship->status();
        $model->first_place_team_id = $finalStandings[1] ?? null;
        $model->second_place_team_id = $finalStandings[2] ?? null;
        $model->third_place_team_id = $finalStandings[3] ?? null;
        $model->fourth_place_team_id = $finalStandings[4] ?? null;
        $model->save();

        $this->syncNewTeamEntries($model, $championship);

        foreach ($championship->games() as $game) {
            GameModel::query()->updateOrCreate(
                [
                    'championship_id' => $model->id,
                    'phase' => $game->phase->value,
                    'position' => $game->position,
                ],
                [
                    'home_team_id' => $game->home->teamId,
                    'away_team_id' => $game->away->teamId,
                    'home_score' => $game->score()?->home,
                    'away_score' => $game->score()?->away,
                    'penalty_home' => $game->penalties()?->home,
                    'penalty_away' => $game->penalties()?->away,
                    'winner_team_id' => $game->winnerTeamId(),
                    'decided_by' => $game->decidedBy()?->value,
                ],
            );
        }
    }

    private function syncNewTeamEntries(ChampionshipModel $model, ChampionshipAggregate $championship): void
    {
        /** @var array<int, int> $existingTeamIds */
        $existingTeamIds = DB::table('championship_team')
            ->where('championship_id', $model->id)
            ->pluck('team_id')
            ->all();

        $newEntries = array_filter(
            $championship->teams(),
            fn (TeamEntry $entry) => ! in_array($entry->teamId, $existingTeamIds, true),
        );

        if ($newEntries === []) {
            return;
        }

        $attach = [];
        foreach ($newEntries as $entry) {
            $attach[$entry->teamId] = ['registration_order' => $entry->registrationOrder];
        }

        $model->teams()->attach($attach);
    }

    public function standings(int $championshipId): array
    {
        return DB::table('championship_team as ct')
            ->join('teams as t', 't.id', '=', 'ct.team_id')
            ->leftJoin('games as g', function ($join) {
                $join->on('g.championship_id', '=', 'ct.championship_id')
                    ->whereNotNull('g.home_score')
                    ->where(fn ($q) => $q->whereColumn('g.home_team_id', 't.id')->orWhereColumn('g.away_team_id', 't.id'));
            })
            ->where('ct.championship_id', $championshipId)
            ->groupBy('t.id', 't.name', 'ct.registration_order')
            ->orderByDesc('points')
            ->orderBy('ct.registration_order')
            ->selectRaw(<<<'SQL'
                t.id AS team_id,
                t.name,
                ct.registration_order,
                COALESCE(SUM(CASE
                    WHEN g.home_team_id = t.id THEN CAST(g.home_score AS SIGNED) - CAST(g.away_score AS SIGNED)
                    WHEN g.away_team_id = t.id THEN CAST(g.away_score AS SIGNED) - CAST(g.home_score AS SIGNED)
                END), 0) AS points
            SQL)
            ->get()
            ->map(function ($row) {
                $row = (array) $row;
                $row['points'] = (int) $row['points'];

                return $row;
            })
            ->all();
    }
}
