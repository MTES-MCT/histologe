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

class SuiviVisiteCreatedToPartnerMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] Visite programmée';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_CREATED_TO_PARTNER;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_created_to_partner_email';
    protected ?string $tagHeader = 'Pro Date Visite Prevue';

    // public function __construct(
    //     protected MailerInterface $mailer,
    //     protected ParameterBagInterface $parameterBag,
    //     protected LoggerInterface $logger,
    //     protected UrlGeneratorInterface $urlGenerator,
    //     protected FailedEmailManager $failedEmailManager,
    // ) {
    //     parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    // }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $intervention = $notificationMail->getIntervention();
        $interventionScheduledAt = $intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y');
        $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';

        return [
            'signalement_adresseOccupant' => $signalement->getAdresseOccupant(),
            'signalement_cpOccupant' => $signalement->getCpOccupant(),
            'signalement_villeOccupant' => $signalement->getVilleOccupant(),
            'intervention_scheduledAt' => $interventionScheduledAt,
            'partner_name' => $partnerName,
            'lien_suivi' => $this->generateLinkSignalementView($signalement->getUuid()),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $signalement = $notificationMail->getSignalement();
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            $signalement->getReference(),
            $signalement->getNomOccupantOrDeclarant()
        );
    }
}
