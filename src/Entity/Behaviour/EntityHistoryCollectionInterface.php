<?php

namespace App\Entity\Behaviour;

interface EntityHistoryCollectionInterface
{
    /**
     * @return array<int, string>
     */
    public function getManyToManyFieldsToTrack(): array;
}
