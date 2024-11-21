<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Manager\FailedEmailManager;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementRefusalMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_REFUSAL_TO_USAGER;
    protected ?string $mailerSubject = 'Votre signalement ne peut pas être traité';
    protected ?string $mailerTemplate = 'refus_signalement_email';
    protected ?string $tagHeader = 'Usager Refus Signalement';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        protected FailedEmailManager $failedEmailManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator, $this->failedEmailManager);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        return [
            'motif' => $notificationMail->getMotif(),
            'territory_name' => $notificationMail->getTerritory()->getName(),
            'signalement_nomOccupant' => $notificationMail->getSignalement()->getNomOccupant(),
            'signalement_prenomOccupant' => $notificationMail->getSignalement()->getPrenomOccupant(),
        ];
    }
}
