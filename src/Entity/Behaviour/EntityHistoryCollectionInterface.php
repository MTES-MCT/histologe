<?php

namespace App\Entity\Behaviour;

interface EntityHistoryCollectionInterface
{
    public function getManyToManyFieldsToTrack(): array;
}
