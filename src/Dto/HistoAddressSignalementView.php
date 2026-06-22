<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['signalements:read'])]
class HistoAddressSignalementView
{
    public function __construct(
        private readonly ?string $url = null,
        private readonly ?string $ref = null,
        private readonly ?string $usager = null,
        private readonly ?string $statut = null,
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
}
