<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteNeededMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] Date de visite à renseigner';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_NEEDED;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_needed_email';
    protected ?string $tagHeader = 'Pro Date Visite Manquante';

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

        return [
            'signalement' => $signalement,
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
