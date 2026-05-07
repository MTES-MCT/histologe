<?php

namespace App\Scheduler\Message;

final readonly class SyncEsaboraSISHInterventionMessage
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
