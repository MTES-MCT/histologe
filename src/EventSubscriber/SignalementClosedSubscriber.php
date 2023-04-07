<?php

namespace App\EventSubscriber;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private SignalementManager $signalementManager,
        private TokenGeneratorInterface $tokenGenerator,
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
        $affectation = $event->getAffectation();
        $params = $event->getParams();
        $user = $this->security->getUser();

        if ($signalement instanceof Signalement) {
            $suivi = $this->suiviManager->createSuivi(
                $user,
                $signalement,
                params: $params,
                isPublic: true
            );
            $signalement
                ->setCodeSuivi($this->tokenGenerator->generateToken())
                ->setClosedBy($user)
                ->addSuivi($suivi);

            $this->sendMailToUsager($signalement);
            $this->sendMailToPartners($signalement);
        }

        if ($affectation instanceof Affectation) {
            $signalement = $affectation->getSignalement();
            $suivi = $this->suiviManager->createSuivi($user, $signalement, $params);
            $signalement->addSuivi($suivi);
            $this->sendMailToPartner(
                signalement: $signalement,
                partnerToExclude: $this->security->getUser()->getPartner()
            );
        }

        $this->signalementManager->save($signalement);
    }

    private function sendMailToUsager(Signalement $signalement): void
    {
        $toRecipients = $signalement->getMailUsagers();
        if (!empty($toRecipients)) {
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
            ));
    }

    private function sendMailToPartner(Signalement $signalement, ?Partner $partnerToExclude = null)
    {
        $sendTo = $this->signalementManager->getRepository()->findUsersPartnerEmailAffectedToSignalement(
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
