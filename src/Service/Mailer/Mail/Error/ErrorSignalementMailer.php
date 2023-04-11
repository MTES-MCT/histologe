<?php

namespace App\Service\Mailer\Mail\Error;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ErrorSignalementMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ERROR_SIGNALEMENT;
    protected ?string $mailerSubject = 'Une erreur est survenue lors de la création d\'un signalement !';
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
        $event = $notificationMail->getEvent();
        $attachment = $notificationMail->getAttachment();

        return [
            'url' => $_SERVER['SERVER_NAME'],
            'code' => $event->getThrowable()->getCode(),
            'error' => $event->getThrowable()->getMessage(),
            'req' => $event->getRequest()->getContent(),
            'signalement' => $event->getRequest()->get('signalement'),
            'attachment' => $attachment,
        ];
    }
}
