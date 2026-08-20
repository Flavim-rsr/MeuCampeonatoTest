<?php

namespace App\Domain\Tournament;

use App\Domain\Contracts\ScoreGeneratorInterface;
use App\Domain\Contracts\ShufflerInterface;
use App\Domain\Exceptions\ChampionshipRuleViolation;
use App\Domain\Scoring\StandingsCalculator;
use App\Domain\Tiebreaker\TiebreakContext;
use App\Domain\Tiebreaker\TiebreakerChain;

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

    public function simulateCurrentPhase(
        ScoreGeneratorInterface $scores,
        TiebreakerChain $chain,
        StandingsCalculator $standings,
        ShufflerInterface $shuffler,
    ): void {
        $phases = match ($this->status) {
            ChampionshipStatus::QuarterFinals => [Phase::QuarterFinals],
            ChampionshipStatus::SemiFinals => [Phase::SemiFinals],
            ChampionshipStatus::Finals => [Phase::ThirdPlace, Phase::Final],
            default => throw ChampionshipRuleViolation::invalidTransition($this->status->value),
        };

        foreach ($phases as $phase) {
            $this->playPhase($phase, $scores, $chain, $standings);
        }

        match ($this->status) {
            ChampionshipStatus::QuarterFinals => $this->drawSemiFinals($shuffler),
            ChampionshipStatus::SemiFinals => $this->drawFinals(),
            ChampionshipStatus::Finals => $this->recordFinalStandings(),
            default => null,
        };

        $this->status = $this->status->next();
    }

    private function playPhase(
        Phase $phase,
        ScoreGeneratorInterface $scores,
        TiebreakerChain $chain,
        StandingsCalculator $standings,
    ): void {
        foreach ($this->sortedGamesOfPhase($phase) as $game) {
            $game->recordScore($scores->generate());
            $score = $game->score();

            if (! $score->isDraw()) {
                $winnerTeamId = $score->winnerSide() === 'home' ? $game->home->teamId : $game->away->teamId;
                $game->decide($winnerTeamId, DecidedBy::Score);

                continue;
            }

            $decision = $chain->resolve(new TiebreakContext($game->home, $game->away, $standings->calculate($this->games)));
            $game->decide($decision->winnerTeamId, $decision->decidedBy, $decision->penalties);
        }
    }

    private function drawSemiFinals(ShufflerInterface $shuffler): void
    {
        $winners = array_map(
            fn (Game $game) => $game->winnerEntry(),
            $this->sortedGamesOfPhase(Phase::QuarterFinals),
        );

        $shuffled = array_values($shuffler->shuffle($winners));

        $this->games[] = new Game(Phase::SemiFinals, 1, $shuffled[0], $shuffled[1]);
        $this->games[] = new Game(Phase::SemiFinals, 2, $shuffled[2], $shuffled[3]);
    }

    private function drawFinals(): void
    {
        [$first, $second] = $this->sortedGamesOfPhase(Phase::SemiFinals);

        $this->games[] = new Game(Phase::ThirdPlace, 1, $first->loserEntry(), $second->loserEntry());
        $this->games[] = new Game(Phase::Final, 1, $first->winnerEntry(), $second->winnerEntry());
    }

    private function recordFinalStandings(): void
    {
        $thirdPlace = $this->sortedGamesOfPhase(Phase::ThirdPlace)[0];
        $final = $this->sortedGamesOfPhase(Phase::Final)[0];

        $this->finalStandings = [
            1 => $final->winnerEntry()->teamId,
            2 => $final->loserEntry()->teamId,
            3 => $thirdPlace->winnerEntry()->teamId,
            4 => $thirdPlace->loserEntry()->teamId,
        ];
    }

    /**
     * @return array<int, Game>
     */
    private function sortedGamesOfPhase(Phase $phase): array
    {
        $games = $this->gamesOfPhase($phase);

        usort($games, fn (Game $a, Game $b) => $a->position <=> $b->position);

        return $games;
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
