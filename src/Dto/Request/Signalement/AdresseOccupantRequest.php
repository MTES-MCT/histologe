<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class AdresseOccupantRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir une adresse.')]
        private readonly ?string $adresse = null,
        #[Assert\NotBlank(message: 'Merci de saisir un code postal.')]
        private readonly ?string $codePostal = null,
        #[Assert\NotBlank(message: 'Merci de saisir une ville.')]
        private readonly ?string $ville = null,
        #[Assert\Length(max: 5)]
        private readonly ?string $etage = null,
        #[Assert\Length(max: 3)]
        private readonly ?string $escalier = null,
        #[Assert\Length(max: 30)]
        private readonly ?string $numAppart = null,
        #[Assert\Length(max: 255)]
        private readonly ?string $autre = null,
        private readonly ?string $geolocLng = null,
        private readonly ?string $geolocLat = null,
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

    public function getGeolocLng(): ?string
    {
        return $this->geolocLng;
    }

    public function getGeolocLat(): ?string
    {
        return $this->geolocLat;
    }

    public function getInsee(): ?string
    {
        return $this->insee;
    }
}