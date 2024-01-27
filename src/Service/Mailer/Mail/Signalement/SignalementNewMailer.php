<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementNewMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_NEW;
    protected ?string $mailerSubject = '[%s] Un nouveau signalement vous attend';
    protected ?string $mailerButtonText = 'Voir le signalement';
    protected ?string $mailerTemplate = 'new_signalement_email';
    protected ?string $tagHeader = 'Pro Nouveau Signalement';

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
        $uuid = $signalement->getUuid();

        return [
            'ref_signalement' => $signalement->getReference(),
            'link' => $this->urlGenerator->generate('back_signalement_view', [
                'uuid' => $uuid,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = sprintf($this->mailerSubject, $notificationMail->getSignalement()->getCpOccupant());
    }
}
