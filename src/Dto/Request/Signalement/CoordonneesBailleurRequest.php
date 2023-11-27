<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesBailleurRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir le nom du bailleur.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO'])]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir le prénom du bailleur.', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir l\'email du bailleur', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(message: 'Merci de saisir le numéro de téléphone du bailleur', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
        #[Assert\NotBlank(message: 'Merci de saisisr l\'adresse du bailleur', groups: ['BAILLEUR_OCCUPANT'])]
        private readonly ?string $adresse = null,
        #[Assert\NotBlank(message: 'Merci de saisir le code postal du bailleur', groups: ['BAILLEUR_OCCUPANT'])]
        private readonly ?string $codePostal = null,
        #[Assert\NotBlank(message: 'Merci de saisir la ville du bailleur', groups: ['BAILLEUR_OCCUPANT'])]
        private readonly ?string $ville = null,
    ) {
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
