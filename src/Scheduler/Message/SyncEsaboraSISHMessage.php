<?php

namespace App\Scheduler\Message;

final readonly class SyncEsaboraSISHMessage
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
