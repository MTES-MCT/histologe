<?php

namespace App\EventSubscriber;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
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
        /** @var User $user */
        $user = $this->security->getUser();

        if ($signalement instanceof Signalement) {
            $suivi = $this->suiviManager->createSuivi(
                $user,
                $signalement,
                params: $params,
                isPublic: '1' == $params['suivi_public']
            );
            $this->suiviManager->save($suivi);

            $signalement
                ->setClosedBy($user)
                ->addSuivi($suivi);

            if ('1' == $params['suivi_public']) {
                $signalement->setCodeSuivi($this->tokenGenerator->generateToken());
                $this->sendMailToUsager($signalement);
            }
            $this->sendMailToPartners($signalement);
        }

        if ($affectation instanceof Affectation) {
            $signalement = $affectation->getSignalement();
            $suivi = $this->suiviManager->createSuivi($user, $signalement, $params);
            $this->suiviManager->save($suivi);

            $signalement->addSuivi($suivi);
            $this->sendMailToPartner(
                signalement: $signalement,
                partnerToExclude: $user->getPartner()
            );
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

    private function sendMailToPartner(Signalement $signalement, ?Partner $partnerToExclude = null)
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
