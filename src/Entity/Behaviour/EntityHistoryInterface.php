<?php

namespace App\Entity\Behaviour;

use App\Entity\Enum\HistoryEntryEvent;

interface EntityHistoryInterface
{
    public function getId(): ?int;

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array;
}
