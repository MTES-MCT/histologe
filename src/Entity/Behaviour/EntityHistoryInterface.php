<?php

namespace App\Entity\Behaviour;

interface EntityHistoryInterface
{
    public function getId(): ?int;

    /** @return array<mixed> */
    public function getHistoryRegisteredEvent(): array;
}
