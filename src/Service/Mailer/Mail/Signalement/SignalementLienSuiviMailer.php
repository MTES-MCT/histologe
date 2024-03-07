<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementLienSuiviMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_LIEN_SUIVI;
    protected ?string $mailerSubject = 'Lien vers votre page de suivi';
    protected ?string $mailerTemplate = 'lien_suivi_signalement_email';
    protected ?string $tagHeader = 'Lien de suivi du signalement';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        return [
            'signalement' => $notificationMail->getSignalement(),
            'lien_suivi' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $notificationMail->getSignalement()->getCodeSuivi(), 'from' => $notificationMail->getTo()]
            ),
        ];
    }
}
