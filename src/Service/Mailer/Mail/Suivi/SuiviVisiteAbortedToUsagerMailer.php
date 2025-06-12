<?php

namespace App\Service\Mailer\Mail\Suivi;

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
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $intervention = $notificationMail->getIntervention();
        $interventionScheduledAt = $intervention->getScheduledAtFormated();

        return [
            'intervention_scheduledAt' => $interventionScheduledAt,
            'lien_suivi' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi()],
            ),
        ];
    }
}
