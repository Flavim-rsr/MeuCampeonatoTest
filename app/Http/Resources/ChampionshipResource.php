<?php

namespace App\Http\Resources;

use App\Domain\Contracts\ChampionshipRepositoryInterface;
use App\Domain\Tournament\ChampionshipStatus;
use App\Domain\Tournament\Phase;
use App\Models\Championship;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin Championship */
class ChampionshipResource extends JsonResource
{
    /**
     * Chronological order of the phases, since the `games` relation on the
     * model orders by the phase column alphabetically (final, quarter_finals,
     * semi_finals, third_place), which does not match tournament progression.
     *
     * @var array<string, int>
     */
    private const PHASE_ORDER = [
        'quarter_finals' => 0,
        'semi_finals' => 1,
        'third_place' => 2,
        'final' => 3,
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gamesLoaded = $this->relationLoaded('games');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'tiebreaker_mode' => $this->tiebreaker_mode->value,
            'teams' => $this->teams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'registration_order' => $team->pivot->registration_order,
            ])->all(),
            'games' => GameResource::collection($gamesLoaded ? $this->sortedGames() : collect()),
            'standings' => $gamesLoaded
                ? app(ChampionshipRepositoryInterface::class)->standings($this->id)
                : [],
            'classification' => $this->classification(),
        ];
    }

    /**
     * Games sorted chronologically (quarter finals, semi finals, third place,
     * final) and then by position within the phase.
     *
     * @return Collection<int, Game>
     */
    private function sortedGames(): Collection
    {
        return $this->games
            ->sortBy(fn (Game $game) => self::PHASE_ORDER[$game->phase->value] * 100 + $game->position)
            ->values();
    }

    /**
     * The top four teams once the championship is finished, or null otherwise.
     *
     * @return array<string, array{id: int, name: string}|null>|null
     */
    private function classification(): ?array
    {
        if ($this->status !== ChampionshipStatus::Finished) {
            return null;
        }

        $teamsById = $this->teams->keyBy('id');

        $named = function (?int $teamId) use ($teamsById): ?array {
            if ($teamId === null || ! $teamsById->has($teamId)) {
                return null;
            }

            /** @var Team $team */
            $team = $teamsById->get($teamId);

            return ['id' => $team->id, 'name' => $team->name];
        };

        return [
            'first' => $named($this->first_place_team_id),
            'second' => $named($this->second_place_team_id),
            'third' => $named($this->third_place_team_id),
            'fourth' => $named($this->fourth_place_team_id),
        ];
    }
}
