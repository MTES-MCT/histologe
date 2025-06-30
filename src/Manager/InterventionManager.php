<?php

namespace App\Manager;

use App\Dto\Api\Request\ArreteRequest;
use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Affectation;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Repository\InterventionRepository;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class InterventionManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionFactory $interventionFactory,
        private readonly PartnerManager $partnerManager,
        private readonly WorkflowInterface $interventionPlanningStateMachine,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
        private readonly FileFactory $fileFactory,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        string $entityName = Intervention::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @throws \Exception
     */
    public function createVisiteFromRequest(Signalement $signalement, VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getDate()) {
            return null;
        }

        $partnerFound = null;
        if ($visiteRequest->getPartner()) {
            $partnerFound = $this->partnerManager->getPartnerIfQualification(
                $visiteRequest->getPartner(),
                Qualification::VISITES,
                $signalement->getTerritory()
            );
            if (!$partnerFound) {
                return null;
            }
        }

        $intervention = new Intervention();
        $intervention->setSignalement($signalement)
            ->setPartner($partnerFound)
            ->setExternalOperator($visiteRequest->getExternalOperator())
            ->setScheduledAt(new \DateTimeImmutable($visiteRequest->getDateTimeUTC()))
            ->setType(InterventionType::VISITE)
            ->setStatus(Intervention::STATUS_PLANNED);

        $this->save($intervention);

        if ($intervention->getScheduledAt()->format('Y-m-d') <= (new \DateTimeImmutable())->format('Y-m-d')) {
            $this->confirmVisiteFromRequest($visiteRequest, $intervention);
        }

        return $intervention;
    }

    public function cancelVisiteFromRequest(VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getIntervention() || !$visiteRequest->getDetails()) {
            return null;
        }

        $intervention = $this->interventionRepository->find($visiteRequest->getIntervention());
        if (!$intervention) {
            return null;
        }

        $intervention->setDetails($visiteRequest->getDetails());
        try {
            $this->interventionPlanningStateMachine->apply($intervention, 'cancel');
            $this->save($intervention);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }

        return $intervention;
    }

    /**
     * @throws \Exception
     */
    public function rescheduleVisiteFromRequest(Signalement $signalement, VisiteRequest $visiteRequest): ?Intervention
    {
        if (!$visiteRequest->getIntervention() || !$visiteRequest->getDate()) {
            return null;
        }

        $intervention = $this->interventionRepository->find($visiteRequest->getIntervention());
        if (!$intervention) {
            return null;
        }

        $partnerFound = null;
        if ($visiteRequest->getPartner()) {
            $partnerFound = $this->partnerManager->getPartnerIfQualification(
                $visiteRequest->getPartner(),
                Qualification::VISITES,
                $signalement->getTerritory()
            );
            if (!$partnerFound) {
                return null;
            }
        }

        $intervention
            ->setPartner($partnerFound)
            ->setExternalOperator($visiteRequest->getExternalOperator())
            ->setScheduledAt(new \DateTimeImmutable($visiteRequest->getDateTimeUTC()));
        $this->save($intervention);

        if ($intervention->getScheduledAt()->format('Y-m-d') <= (new \DateTimeImmutable())->format('Y-m-d')) {
            $this->confirmVisiteFromRequest($visiteRequest, $intervention);
        }

        return $intervention;
    }

    public function confirmVisiteFromRequest(
        VisiteRequest $visiteRequest,
        ?Intervention $intervention = null,
    ): ?Intervention {
        if (!$visiteRequest->getDetails()) {
            return null;
        }

        if (!$intervention && $visiteRequest->getIntervention()) {
            $intervention = $this->interventionRepository->find($visiteRequest->getIntervention());
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
            $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList(
                $intervention->getSignalement(),
                $procedures
            );
        }

        if ($visiteRequest->getDocument()) {
            $document = $visiteRequest->getDocument();
            $intervention->addFile($this->createFile($intervention, $document));
        }

        if ($visiteRequest->isVisiteDone()) {
            $context = ['isUsagerNotified' => $visiteRequest->isUsagerNotified()];
            $this->interventionPlanningStateMachine->apply($intervention, 'confirm', $context);
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

        $intervention = $visiteRequest->getIntervention()
            ? $this->interventionRepository->find($visiteRequest->getIntervention())
            : null;

        if (!$intervention) {
            return null;
        }

        $intervention->setDetails($visiteRequest->getDetails());
        if ($visiteRequest->getDocument()) {
            $document = $visiteRequest->getDocument();
            $rapportDeVisite = $intervention->getRapportDeVisite();
            if ($rapportDeVisite) {
                $intervention->removeFile($rapportDeVisite);
            }
            $intervention->addFile($this->createFile($intervention, $document));
        }
        $this->save($intervention);

        return $intervention;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function createArreteFromRequest(ArreteRequest $arreteRequest, Affectation $affectation, bool &$isNew): ?Intervention
    {
        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreatedFromRequest($arreteRequest);
        $intervention = $this->getRepository()->findOneBy([
            'signalement' => $affectation->getSignalement(),
            'type' => InterventionType::ARRETE_PREFECTORAL,
            'details' => $this->htmlSanitizer->sanitize($description),
        ]);
        if (null === $intervention) {
            $isNew = true;
            $additionalInformation = [
                'arrete_numero' => $arreteRequest->numero,
                'arrete_type' => $arreteRequest->type,
                'arrete_mainlevee_date' => $arreteRequest->mainLeveeDate,
                'arrete_mainlevee_numero' => $arreteRequest->mainLeveeNumero,
            ];
            $intervention = $this->interventionFactory->createInstanceFrom(
                affectation: $affectation,
                type: InterventionType::ARRETE_PREFECTORAL,
                scheduledAt: new \DateTimeImmutable($arreteRequest->date),
                registeredAt: new \DateTimeImmutable(),
                status: Intervention::STATUS_DONE,
                details: InterventionDescriptionGenerator::buildDescriptionArreteCreatedFromRequest($arreteRequest),
                additionalInformation: $additionalInformation,
                concludeProcedures: [ProcedureType::INSALUBRITE]
            );

            $this->save($intervention);
        }

        return $intervention;
    }

    private function createFile(
        Intervention $intervention,
        string $document,
        DocumentType $documentType = DocumentType::PROCEDURE_RAPPORT_DE_VISITE,
    ): File {
        /** @var User $user */
        $user = $this->security->getUser();

        return $this->fileFactory->createInstanceFrom(
            filename: $document,
            title: $document,
            signalement: $intervention->getSignalement(),
            user: $user,
            documentType: $documentType
        );
    }
}
