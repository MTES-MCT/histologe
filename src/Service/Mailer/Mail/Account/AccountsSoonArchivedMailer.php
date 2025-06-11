<?php

namespace App\Service\Mailer\Mail\Account;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountsSoonArchivedMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNTS_SOON_ARCHIVED;
    protected ?string $mailerSubject = 'Comptes inactifs sur %param.platform_name%';
    protected ?string $mailerButtonText = 'Voir la liste des comptes';
    protected ?string $mailerTemplate = 'accounts_soon_archived_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    /**
     * @return array<mixed>
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $usersData = $notificationMail->getParams()['usersData'];
        $link = $this->urlGenerator->generate('back_user_inactive_accounts', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return ['link' => $link, 'usersData' => $usersData];
    }
}
