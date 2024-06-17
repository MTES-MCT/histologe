<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class CountSuivi
{
    public function __construct(
        #[Groups(['widget:read'])]
        private ?float $average = null,
        #[Groups(['widget:read'])]
        private ?int $partner = null,
        #[Groups(['widget:read'])]
        private ?int $usager = null,
        #[Groups(['widget:read'])]
        private ?int $signalementNewSuivi = null,
        #[Groups(['widget:read'])]
        private ?int $signalementNoSuivi = null,
        #[Groups(['widget:read'])]
        private ?int $noSuiviAfter3Relances = null,
    ) {
    }

    public function getAverage(): ?float
    {
        return round($this->average, 1);
    }

    public function getPartner(): ?int
    {
        return $this->partner;
    }

    public function getUsager(): ?int
    {
        return $this->usager;
    }

    public function getSignalementNewSuivi(): ?int
    {
        return $this->signalementNewSuivi;
    }

    public function getSignalementNoSuivi(): ?int
    {
        return $this->signalementNoSuivi;
    }

    public function getNoSuiviAfter3Relances(): ?int
    {
        return $this->noSuiviAfter3Relances;
    }
}
