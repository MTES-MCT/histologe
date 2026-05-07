<?php

namespace App\Scheduler\MessageHandler;

use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraServiceInterface;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractSyncEsaboraMessageHandler
{
    public function __construct(
        protected EsaboraManager $esaboraManager,
        protected AffectationRepository $affectationRepository,
        protected NotificationMailerRegistry $notificationMailerRegistry,
        protected ParameterBagInterface $parameterBag,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    protected function synchronizeStatus(
        EsaboraServiceInterface $esaboraService,
        array|string $partnerType,
        string $cronLabel,
        ?string $uuidSignalement = null,
    ): void {
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: $partnerType,
            uuidSignalement: $uuidSignalement
        );

        $countSyncSuccess = 0;
        $countSyncFailed = 0;
        $count = 0;
        foreach ($affectations as $row) {
            $affectation = $row['affectation'];
            $dossierResponse = $esaboraService->getStateDossier($affectation, $row['signalement_uuid']);
            if (AbstractEsaboraService::hasSuccess($dossierResponse)) {
                $this->esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);
                ++$countSyncSuccess;
            } else {
                ++$countSyncFailed;
            }
            ++$count;
            if (0 === $count % 100) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        $this->notifyStatus($countSyncSuccess, $countSyncFailed, $cronLabel);
    }

    protected function notifyStatus(int $countSyncSuccess, int $countSyncFailed, string $cronLabel): void
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                cronLabel: $cronLabel,
                params: [
                    'count_success' => $countSyncSuccess,
                    'count_failed' => $countSyncFailed,
                    'message_success' => $countSyncSuccess > 1
                        ? 'signalements ont été synchronisés'
                        : 'signalement a été synchronisé',
                    'message_failed' => $countSyncFailed > 1
                        ? 'signalements n\'ont pas été synchronisés'
                        : 'signalement n\'a pas été synchronisé',
                ],
            )
        );
    }
}
