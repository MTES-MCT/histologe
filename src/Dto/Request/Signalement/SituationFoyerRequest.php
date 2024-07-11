<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class SituationFoyerRequest implements RequestInterface
{
    public function __construct(
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Logement social" est incorrect.',
        )]
        private readonly ?string $isLogementSocial = null,
        #[Assert\NotBlank(
            message: 'Veuillez préciser si une demande de relogement a été faite.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "Relogement" est incorrect.',
        )]
        private readonly ?string $isRelogement = null,
        #[Assert\NotBlank(
            message: 'Veuillez préciser si l\'occupant est allocataire.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'CAF', 'MSA'],
            message: 'Le champ "Allocataire" est incorrect.',
        )]
        private readonly ?string $isAllocataire = null,
        #[Assert\DateTime('Y-m-d')]
        #[Assert\When(
            expression: 'this.getIsAllocataire() == "oui" || this.getIsAllocataire() == "CAF" || this.getIsAllocataire() == "MSA"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser la date de naissance.'),
            ],
        )]
        private readonly ?string $dateNaissanceOccupant = null,
        #[Assert\Length(max: 50, maxMessage: 'Le numéro d\'allocataire ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $numAllocataire = null,
        #[Assert\Length(max: 20, maxMessage: 'Le montant de l\'allocation ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $logementSocialMontantAllocation = null,
        #[Assert\NotBlank(message: 'Veuillez définir le champ souhaite quitter le logement.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Souhaite quitter le logement" est incorrect.',
        )]
        private readonly ?string $travailleurSocialQuitteLogement = null,
        #[Assert\When(
            expression: 'this.getTravailleurSocialQuitteLogement() == "oui"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser s\'il y a un préavis de départ.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Préavis de départ" est incorrect.',
        )]
        private readonly ?string $travailleurSocialPreavisDepart = null,
        #[Assert\NotBlank(
            message: 'Veuillez préciser si l\'occupant est accompagné par un travailleur social.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Accompagnement par un travailleur social" est incorrect.',
        )]
        private readonly ?string $travailleurSocialAccompagnement = null,
        #[Assert\Length(max: 50)]
        private readonly ?string $travailleurSocialAccompagnementDeclarant = null,
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "Bénéficiaire du RSA" est incorrect.',
        )]
        private readonly ?string $beneficiaireRsa = null,
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "Bénéficiaire du FSL" est incorrect.',
        )]
        private readonly ?string $beneficiaireFsl = null,
        #[Assert\Length(max: 50)]
        private readonly ?string $revenuFiscal = null,
    ) {
    }

    public function getIsLogementSocial(): ?string
    {
        return $this->isLogementSocial;
    }

    public function getIsRelogement(): ?string
    {
        return $this->isRelogement;
    }

    public function getIsAllocataire(): ?string
    {
        return $this->isAllocataire;
    }

    public function getDateNaissanceOccupant(): ?string
    {
        return $this->dateNaissanceOccupant;
    }

    public function getNumAllocataire(): ?string
    {
        return $this->numAllocataire;
    }

    public function getLogementSocialMontantAllocation(): ?string
    {
        return $this->logementSocialMontantAllocation;
    }

    public function getTravailleurSocialQuitteLogement(): ?string
    {
        return $this->travailleurSocialQuitteLogement;
    }

    public function getTravailleurSocialPreavisDepart(): ?string
    {
        return $this->travailleurSocialPreavisDepart;
    }

    public function getTravailleurSocialAccompagnement(): ?string
    {
        return $this->travailleurSocialAccompagnement;
    }

    public function getTravailleurSocialAccompagnementDeclarant(): ?string
    {
        return $this->travailleurSocialAccompagnementDeclarant;
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
}
