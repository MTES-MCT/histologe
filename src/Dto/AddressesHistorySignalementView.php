<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['signalements:read'])]
class AddressesHistorySignalementView
{
    public function __construct(
        private readonly ?string $url = null,
        private readonly ?string $ref = null,
        private readonly ?string $usager = null,
        private readonly ?string $statut = null,
        private readonly ?string $logementSocial = null,
        private readonly ?string $declarant = null,
    ) {
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getUsager(): ?string
    {
        return $this->usager;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function getLogementSocial(): ?string
    {
        return $this->logementSocial;
    }

    public function getDeclarant(): ?string
    {
        return $this->declarant;
    }
}
