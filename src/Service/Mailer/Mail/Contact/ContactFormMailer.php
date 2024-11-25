<?php

namespace App\Service\Mailer\Mail\Contact;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContactFormMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_CONTACT_FORM;
    protected ?string $mailerSubject = 'Vous avez reçu un message depuis la page %param.platform_name%';
    protected ?string $mailerTemplate = 'nouveau_mail_front';
    protected ?string $tagHeader = 'Contact';

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
        return [
            'nom' => $notificationMail->getFromFullname(),
            'mail' => $notificationMail->getFromEmail(),
            'reply' => $notificationMail->getFromEmail(),
            'message' => $notificationMail->getMessage(),
            'organisme' => $notificationMail->getParams()['organisme'],
            'objet' => $notificationMail->getParams()['objet'],
        ];
    }
}
