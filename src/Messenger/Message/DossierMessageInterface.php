<?php

namespace App\Messenger\Message;

use App\Entity\Enum\PartnerType;

interface DossierMessageInterface
{
    public function getPartnerId(): ?int;

    public function getPartnerType(): ?PartnerType;

    public function getSignalementId(): ?int;

    public function getAction(): ?string;
}
