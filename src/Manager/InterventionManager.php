<?php

namespace App\Manager;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Enum\InterventionStatus;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Repository\InterventionRepository;
use App\Repository\PartnerRepository;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class InterventionManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private Security $security,
        private InterventionRepository $interventionRepository,
        private PartnerRepository $partnerRepository,
        string $entityName = Intervention::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createVisiteFromRequest(Signalement $signalement, VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getDate() || !$visiteRequest->getPartner()) {
            return null;
        }

        $hasFoundPartner = false;
        $visitesPartners = $this->partnerRepository->findPartnersWithQualification(Qualification::VISITES, $signalement->getTerritory());
        foreach ($visitesPartners as $partner) {
            if ($partner->getId() == $visiteRequest->getPartner()) {
                $hasFoundPartner = $partner;
                break;
            }
        }

        if (!$hasFoundPartner) {
            return null;
        }

        $intervention = new Intervention();
        $intervention->setSignalement($signalement)
            ->setPartner($partner)
            ->setDate(new DateTime($visiteRequest->getDate()))
            ->setType(InterventionType::VISITE)
            ->setStatus(InterventionStatus::PLANNED);

        $this->save($intervention);

        return $intervention;
    }
}
