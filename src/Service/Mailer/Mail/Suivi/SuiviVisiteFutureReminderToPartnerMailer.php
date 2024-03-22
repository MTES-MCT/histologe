<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteFutureReminderToPartnerMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] Rappel : visite programmée';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_PARTNER;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_future_reminder_to_partner_email';
    protected ?string $tagHeader = 'Pro Visite Prevue Rappel';

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
        $intervention = $notificationMail->getIntervention();

        return [
            'signalement' => $signalement,
            'intervention' => $intervention,
            'lien_suivi' => $this->generateLinkSignalementView($signalement->getUuid()),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $signalement = $notificationMail->getSignalement();
        $this->mailerSubject = sprintf(
            self::MAILER_SUBJECT,
            $signalement->getReference(),
            $signalement->getNomOccupantOrDeclarant()
        );
    }
}
