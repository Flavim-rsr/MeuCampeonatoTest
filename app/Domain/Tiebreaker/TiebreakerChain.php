<?php

namespace App\Domain\Tiebreaker;

final class TiebreakerChain
{
    /** @var list<TiebreakerStrategyInterface> */
    private readonly array $strategies;

    public function __construct(TiebreakerStrategyInterface ...$strategies)
    {
        $this->strategies = $strategies;
    }

    public function resolve(TiebreakContext $context): TiebreakDecision
    {
        foreach ($this->strategies as $strategy) {
            $decision = $strategy->resolve($context);

            if ($decision instanceof TiebreakDecision) {
                return $decision;
            }
        }

        throw new \LogicException('No tiebreaker strategy was able to decide a winner.');
    }
}
