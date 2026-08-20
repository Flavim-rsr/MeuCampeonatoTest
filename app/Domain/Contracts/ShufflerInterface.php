<?php

namespace App\Domain\Contracts;

interface ShufflerInterface
{
    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    public function shuffle(array $items): array;
}
