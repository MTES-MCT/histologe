<?php

namespace App\Service\Mailer\Mail\Account;

use App\Manager\UserManager;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountActivationBoMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_BO;
    protected ?string $mailerSubject = 'Activez votre compte sur %param.platform_name%';
    protected ?string $mailerButtonText = 'Activer mon compte';
    protected ?string $mailerTemplate = 'login_link_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private readonly UserManager $userManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $user = $notificationMail->getUser();
        $this->userManager->loadUserTokenForUser($user);
        $link = $this->generateLink('activate_account', ['user' => $user->getId(), 'token' => $user->getToken()]);

        return ['link' => $link];
    }
}
