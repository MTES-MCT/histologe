<?php

namespace App\Service\Mailer\Mail\Error;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class ErrorSignalementMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ERROR_SIGNALEMENT;
    protected ?string $mailerSubject = 'Une erreur est survenue lors de la création d\'un signalement !';
    protected ?string $mailerTemplate = 'erreur_signalement_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger);
    }
}
