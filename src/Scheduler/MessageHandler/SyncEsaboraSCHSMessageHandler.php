<?php

namespace App\Scheduler\MessageHandler;

use App\Entity\Enum\AffectationStatus;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Scheduler\Message\SyncEsaboraSCHSMessage;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncEsaboraSCHSMessageHandler extends AbstractSyncEsaboraMessageHandler
{
    private int $nbEventsAdded = 0;
    private int $nbEventFilesAdded = 0;

    public function __construct(
        private readonly EsaboraSCHSService $esaboraService,
        EsaboraManager $esaboraManager,
        AffectationRepository $affectationRepository,
        NotificationMailerRegistry $notificationMailerRegistry,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly SuiviRepository $suiviRepository,
    ) {
        parent::__construct(
            $esaboraManager,
            $affectationRepository,
            $notificationMailerRegistry,
            $parameterBag,
            $entityManager
        );
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function __invoke(SyncEsaboraSCHSMessage $message): void
    {
        $this->synchronizeStatus(
            esaboraService: $this->esaboraService,
            partnerType: DossierMessageSCHS::CAN_SYNC_SCHS_ESABORA,
            cronLabel: '[SCHS] Synchronisation des signalements depuis Esabora',
            uuidSignalement: $message->getUuidSignalement()
        );
        $this->synchronizeEvents($message->getUuidSignalement());
    }

    private function synchronizeEvents(?string $uuidSignalement = null): void
    {
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: DossierMessageSCHS::CAN_SYNC_SCHS_ESABORA,
            uuidSignalement: $uuidSignalement,
            affectationStatus: AffectationStatus::ACCEPTED
        );
        $existingEvents = $this->suiviRepository->findExistingEventsForSCHS();
        $count = 0;
        foreach ($affectations as $row) {
            $affectation = $row['affectation'];
            try {
                $dossierEvents = $this->esaboraService->getDossierEvents($affectation, $row['signalement_uuid']);
                foreach ($dossierEvents->getCollection() as $eventItem) {
                    if (empty($eventItem->getDate()) || isset($existingEvents[$eventItem->getEventId()])) {
                        continue;
                    }
                    $suivi = $this->esaboraManager->createSuiviFromDossierEvent($eventItem, $affectation);
                    ++$this->nbEventsAdded;
                    if (!empty($eventItem->getDocuments())) {
                        try {
                            $dossierEventFiles = $this->esaboraService->getDossierEventFiles($affectation, $eventItem);
                            $this->nbEventFilesAdded += $this->esaboraManager->addFilesToSuiviFromDossierEventFiles(
                                $dossierEventFiles,
                                $suivi
                            );
                        } catch (\Throwable $exception) {
                            $this->logger->error(sprintf('Error while synchronizing events files on signalement %s: %s',
                                $affectation->getSignalement()->getUuid(),
                                $exception->getMessage()
                            ));
                        }
                    }
                }
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('Error while synchronizing events on signalement %s: %s',
                    $row['signalement_uuid'],
                    $exception->getMessage()
                ));
            }
            ++$count;
            if (0 === $count % 100) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        $this->notifyEvents();
    }

    private function notifyEvents(): void
    {
        $msg = sprintf('Synchronized %d new events with %d files', $this->nbEventsAdded, $this->nbEventFilesAdded);
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $msg,
                cronLabel: '[SCHS] Synchronisation des évènements depuis Esabora',
            )
        );
    }
}
