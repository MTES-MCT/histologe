<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesFoyerRequest implements RequestInterface
{
    public function __construct(
        #[Assert\Choice(choices: ['ORGANISME_SOCIETE', 'PARTICULIER'], message: 'Le type de propriétaire est incorrect.')]
        private readonly ?string $typeProprio = null,
        #[Assert\When(
            expression: 'this.getTypeProprio() == "ORGANISME_SOCIETE"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de saisir un nom de structure.'),
            ],
        )]
        private readonly ?string $nomStructure = null,
        #[Assert\NotBlank(message: 'Merci de sélectionner une civilité.', groups: ['LOCATAIRE'])]
        #[Assert\Choice(choices: ['mme', 'mr'], message: 'La civilité est incorrecte.')]
        private readonly ?string $civilite = null,
        #[Assert\NotBlank(
            message: 'Merci de saisir un nom.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO', 'BAILLEUR'])]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(
            message: 'Merci de saisir un prénom.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO', 'BAILLEUR'])]
        #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un email.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'email ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(
            message: 'Merci de saisir un numéro de téléphone.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
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
