<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesAgenceRequest implements RequestInterface
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'La dénomination de l\'agence ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $denomination = null,
        #[Assert\Length(max: 255, maxMessage: 'Le nom de l\'agence ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\Length(max: 255, maxMessage: 'Le prénom de l\'agence ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'email de l\'agence ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
        #[Assert\Length(max: 255, maxMessage: 'L\'adresse de l\'agence ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $adresse = null,
        #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
        private readonly ?string $codePostal = null,
        #[Assert\Length(max: 255, maxMessage: 'La ville de l\'agence ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $ville = null,
    ) {
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getTelephoneBis(): ?string
    {
        return $this->telephoneBis;
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
}
