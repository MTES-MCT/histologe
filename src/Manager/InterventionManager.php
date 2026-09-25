<?php

namespace App\Manager;

use App\Dto\Api\Request\ArreteRequest;
use App\Entity\Affectation;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionEditedEvent;
use App\Event\InterventionRescheduledEvent;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Repository\InterventionRepository;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\TimezoneProvider;
use App\Utils\DateHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class InterventionManager
{
    public function __construct(
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionFactory $interventionFactory,
        #[Target('interventionPlanningStateMachine')]
        private readonly WorkflowInterface $interventionPlanningStateMachine,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
        private readonly FileFactory $fileFactory,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly TimezoneProvider $timezoneProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function updateVisiteFromData(
        Intervention $intervention,
        \DateTimeImmutable $scheduledAt,
        \DateTimeImmutable $scheduledAtTime,
        string|Partner $partnerChoice,
        ?bool $visiteDone = null,
        ?string $fileName = null,
        ?string $eventType = 'create',
    ): void {
        $intervention
            ->setType(InterventionType::VISITE)
            ->setStatus(Intervention::STATUS_PLANNED);
        /** @var User $user */
        $user = $this->security->getUser();
        $createdByPartner = $user->getPartnerInTerritoryOrFirstOne($intervention->getSignalement()->getAddress()->getTerritory());

        $previousDate = $intervention->getScheduledAt();
        $scheduledAtUtc = DateHelper::getDateUTCFromLocalDateAndTime(
            $scheduledAt,
            $scheduledAtTime,
            $this->timezoneProvider->getDateTimezone()
        );
        $intervention->setScheduledAt($scheduledAtUtc);

        if ($partnerChoice instanceof Partner) {
            $intervention->setPartner($partnerChoice);
        }

        $this->confirmOrAbortVisiteFromData($intervention, $createdByPartner, $visiteDone, $fileName);

        $todayDate = new \DateTimeImmutable();
        if ($intervention->getScheduledAt()->format('Y-m-d') > $todayDate->format('Y-m-d')) {
            if ('create' === $eventType) {
                $this->eventDispatcher->dispatch(new InterventionCreatedEvent($intervention, $user, $createdByPartner), InterventionCreatedEvent::NAME);
            } elseif ('reschedule' === $eventType) {
                $this->eventDispatcher->dispatch(new InterventionRescheduledEvent($intervention, $user, $previousDate, $createdByPartner), InterventionRescheduledEvent::NAME);
            }
        }
    }

    public function confirmOrAbortVisiteFromData(
        Intervention $intervention,
        ?Partner $createdByPartner = null,
        ?bool $visiteDone = null,
        ?string $fileName = null,
    ): void {
        $this->attachRapportDeVisiteToIntervention($intervention, $createdByPartner, $fileName);

        $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($intervention->getSignalement(), $intervention->getConcludeProcedure());

        $context['createdByPartner'] = $createdByPartner;
        if (true === $visiteDone) {
            $this->interventionPlanningStateMachine->apply($intervention, 'confirm', $context);
        } elseif (false === $visiteDone) {
            $this->interventionPlanningStateMachine->apply($intervention, 'abort', $context);
        }
    }

    public function editConclusionVisiteFromRequest(
        Intervention $intervention,
        ?Partner $createdByPartner = null,
        ?string $fileName = null,
    ): void {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->attachRapportDeVisiteToIntervention($intervention, $createdByPartner, $fileName);
        $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($intervention->getSignalement(), $intervention->getConcludeProcedure());
        $this->eventDispatcher->dispatch(new InterventionEditedEvent($intervention, $user, $intervention->getNotifyUsager(), $createdByPartner), InterventionEditedEvent::NAME);
    }

    private function attachRapportDeVisiteToIntervention(
        Intervention $intervention,
        ?Partner $createdByPartner = null,
        ?string $fileName = null,
    ): void {
        if ($fileName) {
            /** @var User $user */
            $user = $this->security->getUser();
            $file = $this->fileFactory->createInstanceFrom(
                filename: $fileName,
                title: $fileName,
                signalement: $intervention->getSignalement(),
                partner: $createdByPartner,
                user: $user,
                documentType: DocumentType::PROCEDURE_RAPPORT_DE_VISITE
            );
            $intervention->addFile($file);
        }
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function createArreteFromRequest(ArreteRequest $arreteRequest, Affectation $affectation, bool &$isNew): ?Intervention
    {
        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreatedFromRequest($arreteRequest);
        /** @var Intervention|null $intervention */
        $intervention = $this->interventionRepository->findOneBy([
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

            $this->entityManager->persist($intervention);
            $this->entityManager->flush();
        }

        return $intervention;
    }

    public function createVisiteFromImport(Affectation $affectation, \DateTimeImmutable $dateVisite, string $conclusionVisite): ?Intervention
    {
        $intervention = $this->interventionFactory->createInstanceFrom(
            affectation: $affectation,
            type: InterventionType::VISITE,
            scheduledAt: $dateVisite,
            registeredAt: new \DateTimeImmutable(),
            status: Intervention::STATUS_DONE,
            details: $conclusionVisite,
        );

        $this->entityManager->persist($intervention);
        $this->entityManager->flush();

        return $intervention;
    }
}
