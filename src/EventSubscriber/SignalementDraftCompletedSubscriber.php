<?php

namespace App\EventSubscriber;

use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Event\SignalementDraftCompletedEvent;
use App\Manager\SignalementManager;
use App\Messenger\Message\NewSignalementCheckFileMessage;
use App\Messenger\Message\SignalementDraftProcessMessage;
use App\Service\Files\DocumentProvider;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\SignalementBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class SignalementDraftCompletedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SignalementBuilder $signalementBuilder,
        private readonly SignalementManager $signalementManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly DocumentProvider $documentProvider,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameterBag,
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
                ->withStatus()
                ->build();

            if (null !== $signalement) {
                $this->signalementManager->save($signalement);
                $this->logger->info(sprintf(
                    'Signalement saved with reference #%s in territory %s',
                    $signalement->getReference(),
                    $signalement->getTerritory()->getName()
                ));
                $this->sendNotifications($signalement);
                $this->dispatchDraftProcessing($signalementDraft, $signalement);
                $this->dispatchCheckFiles($signalement);
                $signalementDraft->setStatus(SignalementDraftStatus::EN_SIGNALEMENT);
                $this->entityManager->commit();
            } else {
                $this->entityManager->rollback();
            }
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage());
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    private function sendNotifications(Signalement $signalement): void
    {
        $toRecipients = $signalement->getMailUsagers();
        foreach ($toRecipients as $toRecipient) {
            $type = (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut())
                ? NotificationMailerType::TYPE_CONFIRM_INJONCTION_TO_USAGER
                : NotificationMailerType::TYPE_CONFIRM_RECEPTION_TO_USAGER;
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: $type,
                    to: $toRecipient,
                    territory: $signalement->getTerritory(),
                    signalement: $signalement,
                    attachment: $this->documentProvider->getModeleCourrierPourProprietaire($signalement),
                )
            );
        }
    }

    /**
     * @throws ExceptionInterface
     */
    private function dispatchCheckFiles(Signalement $signalement): void
    {
        $delayInMinutes = (int) $this->parameterBag->get('delay_min_check_new_signalement_files');
        $delayInMs = $delayInMinutes * 60000;
        $this->messageBus->dispatch(
            new NewSignalementCheckFileMessage($signalement->getId()),
            [
                new DelayStamp($delayInMs),
            ]
        );
    }

    /**
     * Dispatch le traitement asynchrone du draft.
     * Les handlers liés à ce message seront exécutés
     * séquentiellement selon leur priorité.
     *
     * @throws ExceptionInterface
     *
     * @see SignalementAddressUpdateAndAutoAssignMessageHandler
     * @see SignalementDraftFileMessageHandler
     */
    private function dispatchDraftProcessing(SignalementDraft $signalementDraft, Signalement $signalement): void
    {
        $this->messageBus->dispatch(
            new SignalementDraftProcessMessage($signalementDraft->getId(), $signalement->getId())
        );
    }
}
