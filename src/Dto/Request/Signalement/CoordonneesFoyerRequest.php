<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesFoyerRequest
{
    public function __construct(
        private readonly ?string $typeProprio = null,
        private readonly ?string $nomStructure = null,
        private readonly ?string $civilite = null,
        #[Assert\NotBlank(message: 'Merci de saisir un nom.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un prénom.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un email', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(message: 'Merci de saisir un numéro de téléphone', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
    ) {
    }

    public function getTypeProprio(): ?string
    {
        return $this->typeProprio;
    }

    public function getNomStructure(): ?string
    {
        return $this->nomStructure;
    }

    public function getCivilite(): ?string
    {
        return $this->civilite;
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
}
