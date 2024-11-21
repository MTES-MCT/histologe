<?php

namespace App\Service\Mailer\Mail\Error;

use App\Manager\FailedEmailManager;
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
    protected ?string $mailerSubject = 'Une erreur est survenue !';
    protected ?string $mailerTemplate = 'erreur_signalement_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        protected FailedEmailManager $failedEmailManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator, $this->failedEmailManager);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $event = $notificationMail->getEvent();

        return [
            'url' => $_SERVER['SERVER_NAME'] ?? 'non défini',
            'code' => $event->getThrowable()->getCode(),
            'error' => $event->getThrowable()->getMessage(),
            'req' => $event->getRequest()->getContent(),
        ];
    }
}
