<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class AdresseOccupantRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir une adresse.')]
        #[Assert\Length(max: 100, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $adresse = null,
        #[Assert\NotBlank(message: 'Merci de saisir un code postal.')]
        #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
        private readonly ?string $codePostal = null,
        #[Assert\NotBlank(message: 'Merci de saisir une ville.')]
        #[Assert\Length(max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $ville = null,
        #[Assert\Length(max: 5, maxMessage: 'L\'étage ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $etage = null,
        #[Assert\Length(max: 3, maxMessage: 'L\'escalier ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $escalier = null,
        #[Assert\Length(max: 5, maxMessage: 'Le numéro d\'appartement ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $numAppart = null,
        #[Assert\Length(max: 255, maxMessage: 'Le champ Autre ne peut pas dépasser {{ limit }} caractères.')]
        private readonly ?string $autre = null,
        #[Assert\Length(max: 50, maxMessage: 'La longitude ne peut pas dépasser {{ limit }} caractères.')]
        #[Assert\Regex(pattern: '/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/', message: 'La longitude doit être un nombre décimal.')]
        private readonly ?string $geolocLng = null,
        #[Assert\Length(max: 50, maxMessage: 'La latitude ne peut pas dépasser {{ limit }} caractères.')]
        #[Assert\Regex(pattern: '/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/', message: 'La latitude doit être un nombre décimal.')]
        private readonly ?string $geolocLat = null,
        #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code insee doit être composé de 5 chiffres.')]
        private readonly ?string $insee = null,
        #[Assert\Choice(choices: ['1'], message: 'Le champ "manual" est incorrect.')]
        private readonly ?string $manual = null,
        #[Assert\Choice(choices: ['1'], message: 'Le champ "needResetInsee" est incorrect.')]
        private readonly ?string $needResetInsee = null,
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

    public function getNeedResetInsee(): ?string
    {
        return $this->needResetInsee;
    }
}
