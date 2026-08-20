<?php

namespace App\Infrastructure\Random;

use App\Domain\Contracts\ShufflerInterface;

final class RandomShuffler implements ShufflerInterface
{
    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    public function shuffle(array $items): array
    {
        $shuffled = $items;

        shuffle($shuffled);

        return $shuffled;
    }
}
