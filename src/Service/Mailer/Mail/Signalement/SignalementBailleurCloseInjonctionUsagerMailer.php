<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementBailleurCloseInjonctionUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_BAILLEUR_CLOSE_INJONCTION_TO_USAGER;
    protected ?string $mailerSubject = 'Votre bailleur indique la fin des travaux concernant votre logement';
    protected ?string $mailerButtonText = 'Accéder à mon dossier';
    protected ?string $brevoTemplateId = '317';
    protected ?string $tagHeader = 'Bailleur Close Signalement To Usager';

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
            'NOM_COMPLET_PROPRIO' => $signalement->getNomProprio().' '.$signalement->getPrenomProprio(),
            'LINK_SIGNALEMENT_USAGER' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi()]
            ),
        ];
    }
}
