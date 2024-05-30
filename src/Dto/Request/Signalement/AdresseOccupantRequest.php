<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class AdresseOccupantRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir une adresse.')]
        private readonly ?string $adresse = null,
        #[Assert\NotBlank(message: 'Merci de saisir un code postal.')]
        private readonly ?string $codePostal = null,
        #[Assert\NotBlank(message: 'Merci de saisir une ville.')]
        private readonly ?string $ville = null,
        #[Assert\Length(max: 5, maxMessage: 'L\'étage ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $etage = null,
        #[Assert\Length(max: 3, maxMessage: 'L\'escalier ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $escalier = null,
        #[Assert\Length(max: 5, maxMessage: 'Le numéro d\'appartement ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $numAppart = null,
        #[Assert\Length(max: 255, maxMessage: 'Le champ Autre ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $autre = null,
        private readonly ?string $geolocLng = null,
        private readonly ?string $geolocLat = null,
        private readonly ?string $insee = null,
        private readonly ?string $manual = null,
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

    public function getManual(): ?string
    {
        return $this->manual;
    }
}
