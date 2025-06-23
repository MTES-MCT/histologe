<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementClosedToAllPartnersMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] Clôture du signalement';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'closed_to_partners_signalement_email';
    protected ?string $tagHeader = 'Pro Cloture Signalement';

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
            'ref_signalement' => $signalement->getReference(),
            'motif_cloture' => $signalement->getMotifCloture()->label(),
            'closed_by' => $signalement->getClosedBy()->getNomComplet(),
            'partner_name' => $signalement->getClosedBy()->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())?->getNom(),
            'link' => $this->generateLinkSignalementView($signalement->getUuid()),
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
