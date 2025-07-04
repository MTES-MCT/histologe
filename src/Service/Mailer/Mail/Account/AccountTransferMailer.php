<?php

namespace App\Service\Mailer\Mail\Account;

use App\Entity\Enum\UserStatus;
use App\Manager\UserManager;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountTransferMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_TRANSFER;
    protected ?string $mailerSubject = 'Transfert de votre compte %param.platform_name%';
    protected ?string $mailerTemplate = 'transfer_account_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,

        private UserManager $userManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    /**
     * @return array<mixed>
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $user = $notificationMail->getUser();
        $this->userManager->loadUserTokenForUser($user);
        if (UserStatus::ACTIVE === $user->getStatut()) {
            $link = $this->generateLink('back_dashboard', []);
        } else {
            $link = $this->generateLink('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        }

        return [
            'btntext' => UserStatus::ACTIVE === $user->getStatut() ? 'Accéder à mon compte' : 'Activer mon compte',
            'link' => $link,
            'user_status' => $user->getStatut()->value,
            'partner_name' => $notificationMail->getParams()['partner_name'],
            'territory_name' => $notificationMail->getTerritory()->getName(),
        ];
    }
}
