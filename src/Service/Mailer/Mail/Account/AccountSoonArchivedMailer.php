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

class AccountSoonArchivedMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_SOON_ARCHIVED;
    protected ?string $mailerSubject = 'Compte inactif sur %param.platform_name%';
    protected ?string $mailerButtonText = 'Réinitialiser mon mot de passe';
    protected ?string $mailerTemplate = 'account_soon_archived_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private readonly UserManager $userManager
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $nbDays = $notificationMail->getParams()['nbDays'];
        $link = $this->urlGenerator->generate('login_mdp_perdu', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return ['link' => $link, 'nbDays' => $nbDays];
    }
}
