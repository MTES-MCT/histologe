<?php

namespace App\Service\Mailer\Mail\Account;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class AccountLostPasswordMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_LOST_PASSWORD;
    protected ?string $mailerSubject = 'Nouveau mot de passe sur %param.platform_name%';
    protected ?string $mailerButtonText = 'Définir mon mot de passe';
    protected ?string $mailerTemplate = 'lost_pass_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($notificationMail->getUser());

        return ['link' => $loginLinkDetails->getUrl()];
    }
}
