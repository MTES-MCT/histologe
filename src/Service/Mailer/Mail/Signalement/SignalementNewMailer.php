<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class SignalementNewMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_NEW;
    protected ?string $mailerSubject = 'Activez votre compte sur Histologe.';
    protected ?string $mailerButtonText = 'Voir le signalement';
    protected ?string $mailerTemplate = 'new_signalement_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger);
    }
}
