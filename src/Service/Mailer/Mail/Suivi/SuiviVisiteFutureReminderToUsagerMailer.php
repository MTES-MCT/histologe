<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteFutureReminderToUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_USAGER;
    protected ?string $mailerButtonText = 'Accéder à mon dossier';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_future_reminder_to_usager_email';
    protected ?string $tagHeader = 'Usager Rappel Visite Prevue';

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
            'lien_suivi' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $notificationMail->getTo()],
            ),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = sprintf('Rappel pour votre visite du %s',
            $notificationMail->getIntervention()->getScheduledAt()->format('d/m/Y')
        );
    }
}
