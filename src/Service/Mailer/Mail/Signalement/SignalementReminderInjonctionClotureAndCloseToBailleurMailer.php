<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementReminderInjonctionClotureAndCloseToBailleurMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_REMINDER_CLOTURE_AND_CLOSE_TO_BAILLEUR;
    protected ?string $mailerSubject = "\u{200b}Fin de la procédure concernant votre logement";
    protected ?string $brevoTemplateId = '319';
    protected ?string $tagHeader = 'Notification bailleur SiLo close démarche';

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
            'ADRESSE_OCCUPANT' => $signalement->getAddressCompleteOccupant(),
            'REFERENCE_INJONCTION' => $signalement->getReferenceInjonction(),
            'NOM_COMPLET_DECLARANT' => $signalement->getPrenomOccupant().' '.$signalement->getNomOccupant(),
            'LINK_DOSSIER_BAILLEUR' => $this->urlGenerator->generate(
                'app_login_bailleur',
                [],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
