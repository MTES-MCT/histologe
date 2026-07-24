<?php

namespace App\EventSubscriber;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\SuiviCreatedEvent;
use App\Service\Notification\NotificationAndMailSender;
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

        if (!$suivi->getSendMail()) {
            return;
        }
        $signalementStatus = $suivi->getSignalement()->getStatut();
        if (in_array($signalementStatus, SignalementStatus::excludedStatuses(includeInjonctionBailleur: false))) {
            return;
        }
        if ($suivi->isWaitingNotification()) {
            return;
        }
        $mentionedPartners = $this->extractMentionedPartners($suivi);
        if (!empty($mentionedPartners)) {
            dump('Mentioned partners', $mentionedPartners);
            // TODO : Les agents abonnés du partenaire mentionné sur le suivi ont une notification {{nom de l'agent - partenaire}} vous a mentionné dans un suivi sur le dossier {{ref}}
            // TODO : Les agents abonnés des autres partenaires ne reçoivent pas de notif
        }
        $this->sendToAdminAndPartners($suivi);
        $this->sendToUsagers($suivi);
        $this->sendToBailleur($suivi);
    }

    private function extractMentionedPartners(Suivi $suivi): array
    {
        if ($suivi->getIsVisibleForUsager() || $suivi->getIsVisibleForBailleur()) {
            return []; // pas de notification de mention si le suivi est visible usager/bailleur
        }

        preg_match_all('/data-partner-id="(\d+)"/', $suivi->getDescription(raw: true), $matches);
$mentionedIds = array_unique(array_map('intval', $matches[1]));
return $mentionedIds;
        // $acceptedPartners = $suivi->getSignalement()->getAffectations()->filter(static fn ($affectation) => AffectationStatus::ACCEPTED === $affectation->getStatut());
        // dump($acceptedPartners);
        // $mentionedPartners = [];
        // foreach ($acceptedPartners as $partner) {
        //     // TODO : rendre plus robuste la mise en forme (car utilisée pour repérer les partenaires mentionnés dans le suivi)
        //     // TODO : essayer de passer par l'id du partenaire ?
        //     if (str_contains($suivi->getDescription(raw: true), '<strong>@'.$partner->getNom().'</strong>')) {
        //         $mentionedPartners[] = $partner;
        //     }
        // }

        // dump($mentionedPartners);

        // return $mentionedPartners;
    }

    private function sendToAdminAndPartners(Suivi $suivi): void
    {
        if (in_array($suivi->getCategory(), SuiviCategory::categoriesNotifyUsagerOnly()) || in_array($suivi->getCategory(), SuiviCategory::injonctionBailleurCategories())) {
            return;
        }
        if (SuiviCategory::SIGNALEMENT_IS_CLOSED === $suivi->getCategory()) {
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
        if ($suivi->getIsVisibleForUsager()) {
            if (SuiviCategory::DEMANDE_ABANDON_PROCEDURE === $suivi->getCategory()) {
                $this->notificationAndMailSender->sendDemandeAbandonProcedureToUsager($suivi);

                return;
            }
            switch ($suivi->getCategory()) {
                case SuiviCategory::SIGNALEMENT_IS_CLOSED:
                    $this->notificationAndMailSender->sendSignalementIsClosedToUsager($suivi);
                    break;
                case SuiviCategory::SIGNALEMENT_IS_REFUSED:
                    $motifDescription = \explode(self::SEPARATOR_MOTIF_REFUS, $suivi->getDescription())[1];
                    $this->notificationAndMailSender->sendSignalementIsRefusedToUsager($suivi, $motifDescription);
                    break;
                case SuiviCategory::SIGNALEMENT_IS_ACTIVE:
                    $this->notificationAndMailSender->sendSignalementIsAcceptedToUsager($suivi);
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

    private function sendToBailleur(Suivi $suivi): void
    {
        if ($suivi->getIsVisibleForBailleur() && !in_array($suivi->getCategory(), SuiviCategory::categoriesSubmittedByBailleur())) {
            $this->notificationAndMailSender->sendNewSuiviToBailleur($suivi);
        }
    }
}
