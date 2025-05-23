<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use App\Validator\DateNaissanceValidatorTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CoordonneesBailleurRequest implements RequestInterface
{
    use DateNaissanceValidatorTrait;

    public function __construct(
        #[Assert\Choice(choices: ['ORGANISME_SOCIETE', 'PARTICULIER'], message: 'Le type de propriétaire est incorrect.')]
        private readonly ?string $typeProprio = null,
        #[Assert\Length(max: 255, maxMessage: 'La dénomination du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $denomination = null,
        #[Assert\NotBlank(
            message: 'Merci de saisir le nom du bailleur.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO'])]
        #[Assert\Length(max: 255, maxMessage: 'Le nom du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir le prénom du bailleur.', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[Assert\Length(max: 255, maxMessage: 'Le prénom du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir l\'email du bailleur.', groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'email du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
        #[Assert\NotBlank(
            message: 'Merci de saisir le numéro de téléphone du bailleur.',
            groups: ['BAILLEUR_OCCUPANT', 'BAILLEUR'])]
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
        #[Assert\NotBlank(message: 'Merci de saisir l\'adresse du bailleur.', groups: ['BAILLEUR_OCCUPANT'])]
        #[Assert\Length(max: 255, maxMessage: 'L\'adresse du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $adresse = null,
        #[Assert\NotBlank(message: 'Merci de saisir le code postal du bailleur.', groups: ['BAILLEUR_OCCUPANT'])]
        #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
        private readonly ?string $codePostal = null,
        #[Assert\NotBlank(message: 'Merci de saisir la ville du bailleur.', groups: ['BAILLEUR_OCCUPANT'])]
        #[Assert\Length(max: 255, maxMessage: 'La ville du bailleur ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $ville = null,
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "bénéficiaire RSA" est incorrect.')]
        private readonly ?string $beneficiaireRsa = null,
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "bénéficiaire FSL" est incorrect.')]
        private readonly ?string $beneficiaireFsl = null,
        #[Assert\Length(max: 50, maxMessage: 'Le revenu fiscal ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $revenuFiscal = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $dateNaissance = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateDateNaissance($this->dateNaissance, 'dateNaissance', $context);
    }

    public function getTypeProprio(): ?string
    {
        return $this->typeProprio;
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
