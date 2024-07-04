<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesTiersRequest implements RequestInterface
{
    public function __construct(
        #[Assert\Choice(choices: ['ORGANISME_SOCIETE', 'PARTICULIER'], message: 'Le type de propriétaire est incorrect.')]
        private readonly ?string $typeProprio = null,
        #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un prénom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un courriel.')]
        #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'email ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(message: 'Merci de saisir un numéro de téléphone.')]
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[Assert\Choice(choices: ['PROCHE', 'VOISIN', 'SECOURS', 'BAILLEUR', 'PRO', 'AUTRE'], message: 'Le lien avec l\'occupant est incorrect.')]
        private readonly ?string $lien = null,
        #[Assert\When(
            expression: 'this.getLien() == "PRO" || this.getLien() == "SECOURS"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de saisir un nom de structure.'),
            ],
        )]
        #[Assert\Length(max: 200, maxMessage: 'Le nom de la structure ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $structure = null,
    ) {
    }

    public function getTypeProprio(): ?string
    {
        return $this->typeProprio;
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
