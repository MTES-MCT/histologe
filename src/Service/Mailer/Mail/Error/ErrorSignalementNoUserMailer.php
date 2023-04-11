<?php

namespace App\Service\Mailer\Mail\Error;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ErrorSignalementNoUserMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ERROR_SIGNALEMENT_NO_USER;
    protected ?string $mailerSubject = 'Aucun utilisateur notifiable pour un signalement !';
    protected ?string $mailerTemplate = 'erreur_signalement_email';

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
        $signalement = $notificationMail->getSignalement();
        $mailType = $notificationMail->getType();

        return [
            'error' => sprintf(
                'Aucun utilisateur est notifiable pour le signalement #%s,
                notification prévue %s (TYPE_SIGNALEMENT_NEW = 3, TYPE_ASSIGNMENT_NEW = 4, TYPE_NEW_COMMENT_BACK = 10)',
                $signalement->getReference(),
                $mailType->name
            ),
        ];
    }
}
