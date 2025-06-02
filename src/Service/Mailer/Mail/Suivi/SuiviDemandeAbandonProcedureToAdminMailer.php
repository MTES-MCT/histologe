<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviDemandeAbandonProcedureToAdminMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = 'Demande de fermeture du dossier %s sur Signal Logement';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_DEMANDE_ABANDON_PROCEDURE_TO_ADMIN;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'demande_abandon_procedure_to_admin';

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
            'reference' => $signalement->getReference(),
            'lien_suivi' => $this->generateLinkSignalementView($signalement->getUuid()),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $signalement = $notificationMail->getSignalement();
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            $signalement->getReference()
        );
    }
}
