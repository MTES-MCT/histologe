<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteRescheduledToUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER;
    protected ?string $mailerSubject = 'Modification de la date de visite de votre logement';
    protected ?string $mailerButtonText = 'Accéder à mon signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_rescheduled_email';

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
        $previousDate = $notificationMail->getPreviousVisiteDate();

        return [
            'signalement' => $signalement,
            'intervention' => $intervention,
            'old_date' => $previousDate,
            'lien_suivi' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $notificationMail->getTo()],
            ),
        ];
    }
}
