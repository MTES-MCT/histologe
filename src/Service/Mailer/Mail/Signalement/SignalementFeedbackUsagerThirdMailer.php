<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Entity\Suivi;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementFeedbackUsagerThirdMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD;
    protected ?string $mailerSubject = '%param.platform_name% : faites le point sur votre problème de logement !';
    protected ?string $mailerTemplate = 'demande_feedback_usager_third_email';
    protected ?string $tagHeader = 'Usager 3e relance';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator, $this->entityManager);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $toRecipient = $notificationMail->getTo();

        return [
            'signalement' => $signalement,
            'from' => $toRecipient,
            'lien_suivi_poursuivre' => $this->generateLink(
                'front_suivi_procedure', [
                    'code' => $signalement->getCodeSuivi(),
                    'from' => $toRecipient,
                    'suiviAuto' => Suivi::POURSUIVRE_PROCEDURE,
                ]
            ),
            'lien_suivi_arreter' => $this->generateLink(
                'front_suivi_procedure',
                ['code' => $signalement->getCodeSuivi(), 'from' => $toRecipient, 'suiviAuto' => Suivi::ARRET_PROCEDURE]
            ),
        ];
    }
}
