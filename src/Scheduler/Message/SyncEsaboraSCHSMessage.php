<?php

namespace App\Scheduler\Message;

final readonly class SyncEsaboraSCHSMessage
{
    public function __construct(
        private ?string $uuidSignalement = null,
    ) {
    }

    public function getUuidSignalement(): ?string
    {
        return $this->uuidSignalement;
    }
}
