<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementReminderInjonctionBailleurMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_REMINDER_TO_BAILLEUR;
    protected ?string $mailerSubject = 'Rappel : envoyez-nous des nouvelles concernant l\'avancée des travaux';
    protected ?string $mailerTemplate = 'reminder_bailleur_injonction_signalement_email';
    protected ?string $tagHeader = 'Bailleur Rappel Avancées Signalement Injonction';

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
        $signalement = $notificationMail->getSignalement();

        return [
            'signalement_adresseOccupant' => $signalement->getAddressCompleteOccupant(),
            'signalement_referenceInjonctionBailleur' => $signalement->getReferenceInjonction(),
        ];
    }
}
