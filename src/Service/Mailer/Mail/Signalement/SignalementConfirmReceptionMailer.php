<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementConfirmReceptionMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_CONFIRM_RECEPTION;
    protected ?string $mailerSubject = 'Votre signalement a bien été reçu !';
    protected ?string $mailerButtonText = 'Accéder à mon signalement';
    protected ?string $mailerTemplate = 'accuse_reception_email';

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
        $signalement = $notificationMail->getSignalement();
        $toRecipient = $notificationMail->getTo();
        $attachment = $notificationMail->getAttachment();

        return [
            'signalement' => $signalement,
            'attach' => $attachment,
            'lien_suivi' => $this->urlGenerator->generate(
                'front_suivi_signalement', [
                'code' => $signalement->getCodeSuivi(),
                'from' => $toRecipient,
            ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
