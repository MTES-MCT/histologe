<?php

namespace App\Manager;

use App\Entity\Signalement;
use App\Repository\PartnerRepository;
use Doctrine\Persistence\ManagerRegistry;

class SignalementManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry,
                                private PartnerRepository $partnerRepository,
                                string $entityName = Signalement::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function findAllPartners(Signalement $signalement): array
    {
        $partners['affected'] = $this->partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: true
        );

        $partners['not_affected'] = $this->partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: false
        );

        return $partners;
    }
}
