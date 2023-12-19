<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesTiersRequest
{
    public function __construct(
        private readonly ?string $typeProprio = null,
        #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un prénom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un courriel.')]
        #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(message: 'Merci de saisir un numéro de téléphone.')]
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        private readonly ?string $lien = null,
        private readonly ?string $structure = null,
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

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function getStructure(): ?string
    {
        return $this->structure;
    }
}
