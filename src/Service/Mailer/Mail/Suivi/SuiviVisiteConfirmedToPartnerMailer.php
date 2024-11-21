<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteConfirmedToPartnerMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] Conclusion de visite disponible';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_PARTNER;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_confirmed_to_partner_email';
    protected ?string $tagHeader = 'Pro Conclusion Visite Disponible';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator, $this->entityManager);
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
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            $signalement->getReference(),
            $signalement->getNomOccupantOrDeclarant()
        );
    }
}
