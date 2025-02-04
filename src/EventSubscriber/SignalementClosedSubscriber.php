<?php

namespace App\EventSubscriber;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private SignalementManager $signalementManager,
        private SuiviManager $suiviManager,
        private Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementClosedEvent::NAME => 'onSignalementClosed',
        ];
    }

    public function onSignalementClosed(SignalementClosedEvent $event): void
    {
        $signalement = $event->getSignalement();
        $params = $event->getParams();
        /** @var User $user */
        $user = $this->security->getUser();

        if ($signalement instanceof Signalement) {
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: SuiviManager::buildDescriptionClotureSignalement($params),
                type: Suivi::TYPE_PARTNER,
                isPublic: '1' == $params['suivi_public'],
                user: $user,
            );

            $signalement
                ->setClosedBy($user)
                ->addSuivi($suivi);

            if ('1' == $params['suivi_public']) {
                $this->sendMailToUsager($signalement);
            }
            $this->sendMailToPartners($signalement);
        }

        $this->signalementManager->save($signalement);
    }

    private function sendMailToUsager(Signalement $signalement): void
    {
        $toRecipients = $signalement->getMailUsagers();
        foreach ($toRecipients as $toRecipient) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_USAGER,
                    to: [$toRecipient],
                    territory: $signalement->getTerritory(),
                    signalement: $signalement
                )
            );
        }
    }

    private function sendMailToPartners(Signalement $signalement): void
    {
        $sendTo = $this->signalementManager->findEmailsAffectedToSignalement($signalement);
        if (empty($sendTo)) {
            return;
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
                to: $sendTo,
                territory: $signalement->getTerritory(),
                signalement: $signalement
            )
        );
    }
}
