<?php

namespace App\Service\Mailer\Mail\Account;

use App\Security\Authenticator\FormLoginAuthenticator;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountReactivationMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ACCOUNT_REACTIVATION;
    protected ?string $mailerSubject = 'Votre compte %param.platform_name% est activé !';
    protected ?string $mailerButtonText = 'Accéder à mon compte';
    protected ?string $mailerTemplate = 'reactive_account_email';

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
        $user = $notificationMail->getUser();
        $partner_name = '';
        $territoire_name = '';
        foreach ($user->getPartners() as $partner) {
            $partner_name .= $partner->getNom().' et ';
        }
        $partner_name = substr($partner_name, 0, -4);
        foreach ($user->getPartnersTerritories() as $territory) {
            $territoire_name .= $territory->getName().'/';
        }
        $territoire_name = substr($territoire_name, 0, -1);

        return [
            'link' => $this->generateLink(FormLoginAuthenticator::LOGIN_ROUTE, ['token' => $user->getToken()]),
            'territoire_name' => $territoire_name,
            'partner_name' => $partner_name,
        ];
    }
}
