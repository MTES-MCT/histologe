<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Manager\FailedEmailManager;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteAbortedToUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_ABORTED_TO_USAGER;
    protected ?string $mailerSubject = 'Visite du logement non effectuée';
    protected ?string $mailerButtonText = 'Accéder à mon dossier';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_aborted_email';
    protected ?string $tagHeader = 'Usager Visite Non Effectuee';

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
        $signalement = $notificationMail->getSignalement();
        $intervention = $notificationMail->getIntervention();
        $interventionScheduledAt = $intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y');

        return [
            'intervention_scheduledAt' => $interventionScheduledAt,
            'lien_suivi' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $notificationMail->getTo()],
            ),
        ];
    }
}
