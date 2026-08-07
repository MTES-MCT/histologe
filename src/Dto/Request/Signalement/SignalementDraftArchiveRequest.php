<?php

namespace App\Dto\Request\Signalement;

class SignalementDraftArchiveRequest
{
    public function __construct(
        public ?string $uuid = null,
    ) {
    }
}
