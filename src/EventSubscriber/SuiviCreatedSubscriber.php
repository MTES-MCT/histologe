<?php

namespace App\EventSubscriber;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\SuiviCreatedEvent;
use App\Service\NotificationAndMailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SuiviCreatedSubscriber implements EventSubscriberInterface
{
    private const string SEPARATOR_MOTIF_REFUS = 'Plus précisément :<br />';

    public function __construct(
        private readonly NotificationAndMailSender $notificationAndMailSender,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SuiviCreatedEvent::NAME => 'onSuiviCreated',
        ];
    }

    public function onSuiviCreated(SuiviCreatedEvent $event): void
    {
        $suivi = $event->getSuivi();

        if (Suivi::TYPE_TECHNICAL === $suivi->getType()) {
            return;
        }
        if (Suivi::CONTEXT_INTERVENTION === $suivi->getContext()) {
            return;
        }
        $signalementStatus = $suivi->getSignalement()->getStatut();
        if (in_array($signalementStatus, SignalementStatus::excludedStatuses(includeInjonctionBailleur: false))) {
            return;
        }
        if ($suivi->isWaitingNotification()) {
            return;
        }
        $this->sendToAdminAndPartners($suivi);
        $this->sendToUsagers($suivi);
    }

    private function sendToAdminAndPartners(Suivi $suivi): void
    {
        if (Suivi::CONTEXT_NOTIFY_USAGER_ONLY === $suivi->getContext()
            // Excludes automatic suivis related to injonction bailleur, without blocking other types of suivis if signalement in status INJONCTION_BAILLEUR
            // -> Avoids too much notifications for now
            || !in_array($suivi->getCategory(), SuiviCategory::injonctionBailleurCategories())) {
            return;
        }
        if (Suivi::CONTEXT_SIGNALEMENT_CLOSED === $suivi->getContext()) {
            $this->notificationAndMailSender->sendSignalementIsClosedToPartners($suivi);
        } elseif (SuiviCategory::DEMANDE_ABANDON_PROCEDURE === $suivi->getCategory()) {
            $this->notificationAndMailSender->sendDemandeAbandonProcedureToAdminsAndPartners($suivi);

            return;
        } else {
            $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners(
                suivi: $suivi,
                sendEmail: (SignalementStatus::CLOSED !== $suivi->getSignalement()->getStatut())
            );
        }
    }

    private function sendToUsagers(Suivi $suivi): void
    {
        if ($suivi->getSendMail() && $suivi->getIsPublic()) {
            if (SuiviCategory::DEMANDE_ABANDON_PROCEDURE === $suivi->getCategory()) {
                $this->notificationAndMailSender->sendDemandeAbandonProcedureToUsager($suivi);

                return;
            }
            switch ($suivi->getContext()) {
                case Suivi::CONTEXT_SIGNALEMENT_ACCEPTED:
                    $this->notificationAndMailSender->sendSignalementIsAcceptedToUsager($suivi);
                    break;
                case Suivi::CONTEXT_SIGNALEMENT_REFUSED:
                    $motifDescription = \explode(self::SEPARATOR_MOTIF_REFUS, $suivi->getDescription())[1];
                    $this->notificationAndMailSender->sendSignalementIsRefusedToUsager($suivi, $motifDescription);
                    break;
                case Suivi::CONTEXT_SIGNALEMENT_CLOSED:
                    $this->notificationAndMailSender->sendSignalementIsClosedToUsager($suivi);
                    break;
                default:
                    if (SignalementStatus::CLOSED !== $suivi->getSignalement()->getStatut()
                            && SignalementStatus::REFUSED !== $suivi->getSignalement()->getStatut()) {
                        $this->notificationAndMailSender->sendNewSuiviToUsagers($suivi);
                    }
                    break;
            }
        }
    }
}
