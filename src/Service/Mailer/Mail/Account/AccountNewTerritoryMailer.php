<?php

namespace App\Service\Mailer\Mail\Account;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Manager\UserManager;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountNewTerritoryMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_NEW_TERRITORY;
    protected ?string $mailerSubject = 'Votre compte a été ajouté au territoire %s';
    protected ?string $mailerTemplate = 'account_new_territory_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,

        private UserManager $userManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $user = $notificationMail->getUser();
        if (UserStatus::ACTIVE === $user->getStatut()) {
            $btntext = 'Me connecter à Signal Logement';
            $link = $this->generateLink('back_dashboard', []);
        } else {
            $this->userManager->loadUserTokenForUser($user);
            $btntext = 'Activer mon compte';
            $link = $this->generateLink('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        }

        return [
            'btntext' => $btntext,
            'link' => $link,
            'partner_name' => $notificationMail->getParams()['partner_name'],
            'territory_name' => $notificationMail->getTerritory()->getName(),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = \sprintf(
            $this->mailerSubject,
            $notificationMail->getTerritory()->getName()
        );
    }
}
