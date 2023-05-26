<?php

namespace App\Manager;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Repository\InterventionRepository;
use App\Repository\PartnerRepository;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use DateTimeImmutable;
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
        private PartnerManager $partnerManager,
        private WorkflowInterface $interventionPlanningStateMachine,
        private SignalementQualificationUpdater $signalementQualificationUpdater,
        string $entityName = Intervention::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createVisiteFromRequest(Signalement $signalement, VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getDate() || !$visiteRequest->getPartner()) {
            return null;
        }

        $partnerFound = $this->partnerManager->getPartnerIfQualification(
            $visiteRequest->getPartner(),
            Qualification::VISITES,
            $signalement->getTerritory()
        );
        if (!$partnerFound) {
            return null;
        }

        $intervention = new Intervention();
        $intervention->setSignalement($signalement)
            ->setPartner($partnerFound)
            ->setScheduledAt(new DateTimeImmutable($visiteRequest->getDateTime()))
            ->setType(InterventionType::VISITE)
            ->setStatus(Intervention::STATUS_PLANNED);

        $this->save($intervention);

        if ($intervention->getScheduledAt() <= new DateTimeImmutable()) {
            $this->confirmVisiteFromRequest($visiteRequest, $intervention);
        }

        return $intervention;
    }

    public function cancelVisiteFromRequest(VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getIntervention() || !$visiteRequest->getDetails()) {
            return null;
        }

        $intervention = $this->interventionRepository->findOneBy(['id' => $visiteRequest->getIntervention()]);
        if (!$intervention) {
            return null;
        }

        $intervention->setDetails($visiteRequest->getDetails());
        $this->interventionPlanningStateMachine->apply($intervention, 'cancel');
        $this->save($intervention);

        return $intervention;
    }

    public function rescheduleVisiteFromRequest(Signalement $signalement, VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getIntervention() || !$visiteRequest->getDate() || !$visiteRequest->getPartner()) {
            return null;
        }

        $intervention = $this->interventionRepository->findOneBy(['id' => $visiteRequest->getIntervention()]);
        if (!$intervention) {
            return null;
        }

        $partnerFound = $this->partnerManager->getPartnerIfQualification(
            $visiteRequest->getPartner(),
            Qualification::VISITES,
            $signalement->getTerritory()
        );
        if (!$partnerFound) {
            return null;
        }

        $intervention
            ->setPartner($partnerFound)
            ->setScheduledAt(new DateTimeImmutable($visiteRequest->getDateTime()));
        $this->save($intervention);

        if ($intervention->getScheduledAt() <= new DateTimeImmutable()) {
            $this->confirmVisiteFromRequest($visiteRequest, $intervention);
        }

        return $intervention;
    }

    public function confirmVisiteFromRequest(VisiteRequest $visiteRequest, ?Intervention $intervention = null): ?Intervention
    {
        if (!$visiteRequest->getDetails()) {
            return null;
        }

        if (!$intervention && $visiteRequest->getIntervention()) {
            $intervention = $this->interventionRepository->findOneBy(['id' => $visiteRequest->getIntervention()]);
        }
        if (!$intervention) {
            return null;
        }

        $intervention
            ->setDetails($visiteRequest->getDetails())
            ->setOccupantPresent($visiteRequest->isOccupantPresent())
            ->setProprietairePresent($visiteRequest->isProprietairePresent());

        if ($visiteRequest->isVisiteDone() && $visiteRequest->getConcludeProcedure()) {
            $procedures = [];
            foreach ($visiteRequest->getConcludeProcedure() as $concludeProcedure) {
                $procedures[] = ProcedureType::tryFrom($concludeProcedure);
            }
            $intervention->setConcludeProcedure($procedures);
            $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($intervention->getSignalement(), $procedures);
        }

        if ($visiteRequest->getDocument()) {
            $intervention->setDocuments([$visiteRequest->getDocument()]);
        }

        if ($visiteRequest->isVisiteDone()) {
            $this->interventionPlanningStateMachine->apply($intervention, 'confirm');
        } else {
            $this->interventionPlanningStateMachine->apply($intervention, 'abort');
        }

        $this->save($intervention);

        return $intervention;
    }

    public function editVisiteFromRequest(VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getDetails()) {
            return null;
        }

        $intervention = $visiteRequest->getIntervention() ? $this->interventionRepository->findOneBy(['id' => $visiteRequest->getIntervention()]) : null;
        if (!$intervention) {
            return null;
        }

        $intervention->setDetails($visiteRequest->getDetails());
        if ($visiteRequest->getDocument()) {
            $intervention->setDocuments([$visiteRequest->getDocument()]);
        }
        $this->save($intervention);

        return $intervention;
    }
}
