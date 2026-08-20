<?php

use App\Domain\Tournament\ChampionshipStatus;

test('status advances draft to finished', function () {
    expect(ChampionshipStatus::Draft->next())->toBe(ChampionshipStatus::QuarterFinals)
        ->and(ChampionshipStatus::QuarterFinals->next())->toBe(ChampionshipStatus::SemiFinals)
        ->and(ChampionshipStatus::SemiFinals->next())->toBe(ChampionshipStatus::Finals)
        ->and(ChampionshipStatus::Finals->next())->toBe(ChampionshipStatus::Finished);
});
