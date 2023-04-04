<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class SignalementValidationMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_VALIDATION;
    protected ?string $mailerSubject = 'Votre signalement est validé !';
    protected ?string $mailerButtonText = 'Suivre mon signalement';
    protected ?string $mailerTemplate = 'validation_signalement_email';

    public function __construct(
    protected MailerInterface $mailer,
    protected ParameterBagInterface $parameterBag,
    protected LoggerInterface $logger
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger);
    }
}
