<?php

namespace App\Service\Mailer\Mail\Account;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class AccountLostPassword extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_ACTIVATION;
    protected ?string $mailerSubject = 'Nouveau mot de passe sur Histologe';
    protected ?string $mailerButtonText = 'Définir mon mot de passe';
    protected ?string $mailerTemplate = 'lost_pass_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger);
    }
}
