<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesBailleurRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir le nom du bailleur.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO'])]
        #[Assert\Length(max: 255, maxMessage: 'Le nom du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir le prénom du bailleur.', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[Assert\Length(max: 255, maxMessage: 'Le prénom du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir l\'email du bailleur', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(message: 'Merci de saisir le numéro de téléphone du bailleur', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
        #[Assert\NotBlank(message: 'Merci de saisir l\'adresse du bailleur', groups: ['BAILLEUR_OCCUPANT'])]
        private readonly ?string $adresse = null,
        #[Assert\NotBlank(message: 'Merci de saisir le code postal du bailleur', groups: ['BAILLEUR_OCCUPANT'])]
        private readonly ?string $codePostal = null,
        #[Assert\NotBlank(message: 'Merci de saisir la ville du bailleur', groups: ['BAILLEUR_OCCUPANT'])]
        private readonly ?string $ville = null,
        private readonly ?string $beneficiaireRsa = null,
        private readonly ?string $beneficiaireFsl = null,
        private readonly ?string $revenuFiscal = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $dateNaissance = null,
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

    public function getBeneficiaireRsa(): ?string
    {
        return $this->beneficiaireRsa;
    }

    public function getBeneficiaireFsl(): ?string
    {
        return $this->beneficiaireFsl;
    }

    public function getRevenuFiscal(): ?string
    {
        return $this->revenuFiscal;
    }

    public function getDateNaissance(): ?string
    {
        return $this->dateNaissance;
    }
}
