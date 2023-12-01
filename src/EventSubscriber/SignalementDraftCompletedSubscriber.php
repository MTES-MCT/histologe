<?php

namespace App\EventSubscriber;

use App\Entity\Enum\ProfileDeclarant;
use App\Event\SignalementDraftCompletedEvent;
use App\Manager\SignalementManager;
use App\Service\Files\DocumentProvider;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\SignalementBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementDraftCompletedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SignalementBuilder $signalementBuilder,
        private SignalementManager $signalementManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private DocumentProvider $documentProvider,
        private AutoAssigner $autoAssigner,
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
        $signalementDraft = $event->getSignalementDraft();

        $signalement = $this->signalementBuilder
            ->createSignalementBuilderFrom($signalementDraft)
            ->withAdressesCoordonnees()
            ->withTypeCompositionLogement()
            ->withSituationFoyer()
            ->withProcedure()
            ->withInformationComplementaire()
            ->withFiles()
            ->build();

        $this->signalementManager->save($signalement);

        if (ProfileDeclarant::LOCATAIRE === $signalement->getProfileDeclarant()
            || ProfileDeclarant::BAILLEUR_OCCUPANT === $signalementDraft->getProfileDeclarant()
        ) {
            $toRecipients = [$signalement->getMailDeclarant()];
        } else {
            $toRecipients = $signalement->getMailUsagers();
        }

        foreach ($toRecipients as $toRecipient) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CONFIRM_RECEPTION,
                    to: $toRecipient,
                    territory: $signalement->getTerritory(),
                    signalement: $signalement,
                    attachment: $this->documentProvider->getModeleCourrierPourProprietaire($signalement),
                )
            );
        }

        $this->autoAssigner->assign($signalement);
    }
}
