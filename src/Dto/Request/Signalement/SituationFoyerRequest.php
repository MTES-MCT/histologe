<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class SituationFoyerRequest implements RequestInterface
{
    public function __construct(
        private readonly ?string $isLogementSocial = null,
        #[Assert\NotBlank(
            message: 'Veuillez préciser si une demande de relogement a été faite.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT']
        )]
        private readonly ?string $isRelogement = null,
        #[Assert\NotBlank(
            message: 'Veuillez préciser si l\'occupant est allocataire.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO']
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
        private readonly ?string $numAllocataire = null,
        private readonly ?string $logementSocialMontantAllocation = null,
        #[Assert\NotBlank(message: 'Veuillez définir le champ souhaite quitter le logement.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO']
        )]
        private readonly ?string $travailleurSocialQuitteLogement = null,
        #[Assert\When(
            expression: 'this.getTravailleurSocialQuitteLogement() == "oui"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser s\'il y a un préavis de départ.'),
            ],
        )]
        private readonly ?string $travailleurSocialPreavisDepart = null,
        #[Assert\NotBlank(
            message: 'Veuillez préciser si l\'occupant est accompagné par un travailleur social.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO']
        )]
        private readonly ?string $travailleurSocialAccompagnementDeclarant = null,
        private readonly ?string $beneficiaireRsa = null,
        private readonly ?string $beneficiaireFsl = null,
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
