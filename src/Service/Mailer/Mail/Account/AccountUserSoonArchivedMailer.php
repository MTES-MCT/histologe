<?php

namespace App\Service\Mailer\Mail\Account;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountUserSoonArchivedMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_USER_SOON_ARCHIVED;
    protected ?string $mailerSubject = 'Suppression de votre compte Signal Logement (ex Histologe)';
    protected ?string $mailerButtonText = 'Connexion à Signal Logement';
    protected ?string $mailerTemplate = 'account_user_soon_archived_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $link = $this->generateLink('back_dashboard', []);
        $futureDate = new \DateTime();
        $futureDate->modify('+15 days');

        return ['link' => $link, 'date' => $futureDate->format('d/m/Y')];
    }
}
