<?php

namespace App\Service\Mailer\Mail\Account;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class AccountActivationNotificationMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_ACTIVATION;
    protected ?string $mailerSubject = 'Activez votre compte sur Histologe';
    protected ?string $mailerButtonText = 'Activer mon compte';
    protected ?string $mailerTemplate = 'login_link_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger);
    }
}
