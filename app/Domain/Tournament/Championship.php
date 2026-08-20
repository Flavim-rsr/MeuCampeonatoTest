<?php

namespace App\Domain\Tournament;

use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Exceptions\ChampionshipRuleViolation;

final class Championship
{
    public const REQUIRED_TEAMS = 8;

    /** @var array<int, TeamEntry> */
    private array $teams;

    /** @var array<int, Game> */
    private array $games;

    /** @var array<int, int> */
    private array $finalStandings;

    /**
     * @param  array<int, TeamEntry>  $teams
     * @param  array<int, Game>  $games
     * @param  array<int, int>  $finalStandings
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly TiebreakerMode $tiebreakerMode,
        private ChampionshipStatus $status = ChampionshipStatus::Draft,
        array $teams = [],
        array $games = [],
        array $finalStandings = [],
    ) {
        $this->teams = $teams;
        $this->games = $games;
        $this->finalStandings = $finalStandings;
    }

    public function enroll(int $teamId, string $name): TeamEntry
    {
        if ($this->status !== ChampionshipStatus::Draft) {
            throw ChampionshipRuleViolation::notInDraft();
        }

        if (count($this->teams) >= self::REQUIRED_TEAMS) {
            throw ChampionshipRuleViolation::enrollmentLimitReached();
        }

        foreach ($this->teams as $team) {
            if ($team->teamId === $teamId) {
                throw ChampionshipRuleViolation::teamAlreadyEnrolled();
            }
        }

        $entry = new TeamEntry($teamId, $name, count($this->teams) + 1);

        $this->teams[] = $entry;

        return $entry;
    }

    public function start(ShufflerInterface $shuffler): void
    {
        if ($this->status !== ChampionshipStatus::Draft) {
            throw ChampionshipRuleViolation::invalidTransition($this->status->value);
        }

        if (count($this->teams) !== self::REQUIRED_TEAMS) {
            throw ChampionshipRuleViolation::wrongTeamCount(count($this->teams));
        }

        $shuffled = array_values($shuffler->shuffle($this->teams));

        $position = 1;
        for ($i = 0; $i < count($shuffled); $i += 2) {
            $this->games[] = new Game(Phase::QuarterFinals, $position, $shuffled[$i], $shuffled[$i + 1]);
            $position++;
        }

        $this->status = $this->status->next();
    }

    public function status(): ChampionshipStatus
    {
        return $this->status;
    }

    /**
     * @return array<int, TeamEntry>
     */
    public function teams(): array
    {
        $teams = $this->teams;

        usort($teams, fn (TeamEntry $a, TeamEntry $b) => $a->registrationOrder <=> $b->registrationOrder);

        return $teams;
    }

    /**
     * @return array<int, Game>
     */
    public function games(): array
    {
        return $this->games;
    }

    /**
     * @return array<int, Game>
     */
    public function gamesOfPhase(Phase $phase): array
    {
        return array_values(array_filter(
            $this->games,
            fn (Game $game) => $game->phase === $phase,
        ));
    }

    /**
     * @return array<int, int>
     */
    public function finalStandings(): array
    {
        return $this->finalStandings;
    }
}
