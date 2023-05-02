<?php

namespace App\Manager;

use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Repository\PartnerRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartnerManager extends AbstractManager
{
    public function __construct(
        private PartnerRepository $partnerRepository,
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = Partner::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function getPartnerIfQualification(int $idPartner, Qualification $qualification, Territory $territory): ?Partner
    {
        $visitesPartners = $this->partnerRepository->findPartnersWithQualification($qualification, $territory);
        foreach ($visitesPartners as $partner) {
            if ($partner->getId() == $idPartner) {
                return $partner;
            }
        }

        return null;
    }
}
