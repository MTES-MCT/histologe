<?php

namespace App\Manager;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Repository\InterventionRepository;
use App\Repository\PartnerRepository;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\WorkflowInterface;

class InterventionManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private Security $security,
        private InterventionRepository $interventionRepository,
        private PartnerRepository $partnerRepository,
        private WorkflowInterface $interventionPlanningStateMachine,
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
            ->setStatus(Intervention::STATUS_PLANNED);

        $this->save($intervention);

        return $intervention;
    }

    public function cancelVisiteFromRequest(Signalement $signalement, VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getIntervention() || !$visiteRequest->getDetails()) {
            return null;
        }

        $intervention = $this->interventionRepository->findOneBy(['id' => $visiteRequest->getIntervention()]);
        if (!$intervention) {
            return null;
        }

        $this->interventionPlanningStateMachine->apply($intervention, 'cancel');
        $intervention->setDetails($visiteRequest->getDetails());
        $this->save($intervention);

        return $intervention;
    }
}
