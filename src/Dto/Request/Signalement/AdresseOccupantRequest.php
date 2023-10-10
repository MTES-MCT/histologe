<?php

namespace App\Dto\Request\Signalement;

class AdresseOccupantRequest
{
    public function __construct(
        private readonly ?string $adresse = null,
        private readonly ?string $codePostal = null,
        private readonly ?string $ville = null,
        private readonly ?string $etage = null,
        private readonly ?string $escalier = null,
        private readonly ?string $numAppart = null,
        private readonly ?string $autre = null,
        private readonly ?string $geoloc = null,
        private readonly ?string $insee = null,
    ) {
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function getEtage(): ?string
    {
        return $this->etage;
    }

    public function getEscalier(): ?string
    {
        return $this->escalier;
    }

    public function getNumAppart(): ?string
    {
        return $this->numAppart;
    }

    public function getAutre(): ?string
    {
        return $this->autre;
    }

    public function getGeoloc(): ?string
    {
        return $this->geoloc;
    }

    public function getInsee(): ?string
    {
        return $this->insee;
    }
}
