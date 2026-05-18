<?php

namespace App\Scheduler\MessageHandler;

use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Repository\AffectationRepository;
use App\Scheduler\Message\SyncEsaboraSISHMessage;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Mailer\NotificationMailerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncEsaboraSISHMessageHandler extends AbstractSyncEsaboraMessageHandler
{
    public function __construct(
        private readonly EsaboraSISHService $esaboraService,
        EsaboraManager $esaboraManager,
        AffectationRepository $affectationRepository,
        NotificationMailerRegistry $notificationMailerRegistry,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
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
    public function __invoke(SyncEsaboraSISHMessage $message): void
    {
        $this->synchronizeStatus(
            esaboraService: $this->esaboraService,
            partnerType: DossierMessageSISH::CAN_SYNC_SISH_ESABORA,
            cronLabel: '[SISH] Synchronisation des signalements depuis Esabora',
            uuidSignalement: $message->getUuidSignalement()
        );
    }
}
