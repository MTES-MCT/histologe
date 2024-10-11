<?php

namespace App\EventSubscriber;

use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Event\SignalementDraftCompletedEvent;
use App\Manager\SignalementManager;
use App\Messenger\Message\SignalementDraftFileMessage;
use App\Service\Files\DocumentProvider;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\SignalementBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SignalementDraftCompletedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SignalementBuilder $signalementBuilder,
        private readonly SignalementManager $signalementManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly DocumentProvider $documentProvider,
        private readonly AutoAssigner $autoAssigner,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementDraftCompletedEvent::NAME => 'onSignalementDraftCompleted',
        ];
    }

    public function onSignalementDraftCompleted(SignalementDraftCompletedEvent $event): void
    {
        try {
            $this->entityManager->beginTransaction();
            $signalementDraft = $event->getSignalementDraft();

            $signalement = $this->signalementBuilder
                ->createSignalementBuilderFrom($signalementDraft)
                ->withAdressesCoordonnees()
                ->withTypeCompositionLogement()
                ->withSituationFoyer()
                ->withProcedure()
                ->withInformationComplementaire()
                ->withDesordres()
                ->build();

            if (null !== $signalement) {
                $this->signalementManager->save($signalement);
                $this->entityManager->commit();
                $this->logger->info(sprintf(
                    'Signalement saved with reference #%s in territory %s',
                    $signalement->getReference(),
                    $signalement->getTerritory()->getName()
                ));
                $this->sendNotifications($signalement);
                $this->processFiles($signalementDraft, $signalement);
                $this->autoAssigner->assign($signalement);
            } else {
                $this->entityManager->rollback();
            }
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage());
            $this->entityManager->rollback();
        }
    }

    private function sendNotifications(Signalement $signalement): void
    {
        $toRecipients = $signalement->getMailUsagers();
        foreach ($toRecipients as $toRecipient) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CONFIRM_RECEPTION_TO_USAGER,
                    to: $toRecipient,
                    territory: $signalement->getTerritory(),
                    signalement: $signalement,
                    attachment: $this->documentProvider->getModeleCourrierPourProprietaire($signalement),
                )
            );
        }
    }

    private function processFiles(SignalementDraft $signalementDraft, Signalement $signalement): void
    {
        $this->messageBus->dispatch(new SignalementDraftFileMessage(
            $signalementDraft->getId(),
            $signalement->getId()
        ));
    }
}
