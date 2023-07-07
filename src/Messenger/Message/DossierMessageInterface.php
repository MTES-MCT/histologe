<?php

namespace App\Messenger\Message;

interface DossierMessageInterface
{
    public function getPartnerId(): ?int;

    public function getSignalementId(): ?int;
}
