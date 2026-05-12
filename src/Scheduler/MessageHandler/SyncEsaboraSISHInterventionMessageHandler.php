<?php

namespace App\Scheduler\MessageHandler;

use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Scheduler\Message\SyncEsaboraSISHInterventionMessage;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\Handler\InterventionSISHHandlerInterface;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncEsaboraSISHInterventionMessageHandler
{
    /** @var iterable<InterventionSISHHandlerInterface> */
    private iterable $interventionHandlers;

    /** @param iterable<InterventionSISHHandlerInterface> $interventionHandlers */
    public function __construct(
        private readonly JobEventRepository $jobEventRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        #[AutowireIterator(
            'app.intervention_sish_handler',
            defaultPriorityMethod: 'getPriority'
        )] iterable $interventionHandlers,
    ) {
        $this->interventionHandlers = $interventionHandlers;
    }

    public function __invoke(SyncEsaboraSISHInterventionMessage $message): void
    {
        $errorMessages = [];
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: DossierMessageSISH::CAN_SYNC_SISH_ESABORA,
            uuidSignalement: $message->getUuidSignalement()
        );
        $count = 0;
        foreach ($affectations as $row) {
            $affectation = $row['affectation'];
            /** @var InterventionSISHHandlerInterface $interventionHandler */
            foreach ($this->interventionHandlers as $interventionHandler) {
                try {
                    $interventionHandler->handle($affectation, $row['signalement_uuid']);
                } catch (\Throwable $e) {
                    $signalement = $affectation->getSignalement();
                    $message = $interventionHandler->getServiceName()
                        .' - Signalement '.$row['signalement_uuid']
                        .' ('.$signalement->getId().') : '
                        .$e->getMessage();
                    if (!$e instanceof \Exception) {
                        $message .= ' - '.$e->getFile().' ('.$e->getLine().')';
                    }
                    $this->logger->error($message);
                    $errorMessages[] = $message;
                }
            }
            ++$count;
            if (0 === $count % 100) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();

        ['success_count' => $countSuccess, 'failed_count' => $countFailed] =
             $this->jobEventRepository->getReportEsaboraAction(
                 AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
                 AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE
             );

        $this->notify($countSuccess, $countFailed, $errorMessages);
    }

    /**
     * @param string[] $errorMessages
     */
    private function notify(int $countSuccess, int $countFailed, array $errorMessages): void
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                cronLabel: '[ARS] Synchronisation des interventions depuis Esabora',
                params: [
                    'count_success' => $countSuccess,
                    'count_failed' => $countFailed,
                    'message_success' => $countSuccess > 1
                        ? 'synchronisations ont été effectuées'
                        : 'synchronisation effectuée',
                    'message_failed' => $countSuccess > 1
                        ? 'synchronisations en échec'
                        : 'synchronisation en échec',
                    'error_messages' => $errorMessages,
                ],
            )
        );
    }
}
