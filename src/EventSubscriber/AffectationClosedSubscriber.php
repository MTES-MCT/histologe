<?php

namespace App\EventSubscriber;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\AffectationClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Service\NotificationAndMailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class AffectationClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly SignalementManager $signalementManager,
        private readonly SuiviManager $suiviManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AffectationClosedEvent::NAME => 'onAffectationClosed',
        ];
    }

    public function onAffectationClosed(AffectationClosedEvent $event): void
    {
        $user = $event->getUser();
        $affectation = $event->getAffectation();
        $signalement = $affectation->getSignalement();
        $params['subject'] = $affectation->getPartner()->getNom();
        $params['motif_cloture'] = $affectation->getMotifCloture();
        $params['motif_suivi'] = $event->getMessage();
        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: SuiviManager::buildDescriptionClotureSignalement($params),
            type: Suivi::TYPE_PARTNER,
            user: $user,
            category: SuiviCategory::AFFECTATION_IS_CLOSED,
        );

        $signalement->addSuivi($suivi);
        $this->notificationAndMailSender->sendAffectationClosed($affectation, $user);
        $this->signalementManager->save($signalement);
    }
}
