<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class SituationFoyerRequest
{
    public function __construct(
        private readonly ?string $isLogementSocial = null,
        private readonly ?string $isRelogement = null,
        private readonly ?string $isAllocataire = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $dateNaissanceOccupant = null,
        private readonly ?string $numAllocataire = null,
        private readonly ?string $logementSocialMontantAllocation = null,
        private readonly ?string $travailleurSocialQuitteLogement = null,
        private readonly ?string $travailleurSocialAccompagnementDeclarant = null,
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

    public function getTravailleurSocialAccompagnementDeclarant(): ?string
    {
        return $this->travailleurSocialAccompagnementDeclarant;
    }
}
