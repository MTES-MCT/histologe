<?php

namespace App\EventSubscriber;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Event\AffectationClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class AffectationClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private SignalementManager $signalementManager,
        private SuiviManager $suiviManager,
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
        );

        $signalement->addSuivi($suivi);
        $this->sendMailToPartner(
            signalement: $signalement,
            partnerToExclude: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())
        );

        $this->signalementManager->save($signalement);
    }

    private function sendMailToPartner(Signalement $signalement, ?Partner $partnerToExclude = null): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $sendTo = $signalementRepository->findUsersPartnerEmailAffectedToSignalement(
            $signalement->getId(),
            $partnerToExclude
        );

        if (empty($sendTo)) {
            return;
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNER,
                to: $sendTo,
                territory: $signalement->getTerritory(),
                signalement: $signalement,
            )
        );
    }
}
