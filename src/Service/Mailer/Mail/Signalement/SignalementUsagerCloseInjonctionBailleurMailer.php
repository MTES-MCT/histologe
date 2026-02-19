<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementUsagerCloseInjonctionBailleurMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_USAGER_CLOSE_INJONCTION_TO_BAILLEUR;
    protected ?string $mailerSubject = 'Votre locataire a mis fin à la procédure concernant votre logement';
    protected ?string $mailerButtonText = 'Accéder à mon dossier';
    protected ?string $brevoTemplateId = '296';
    protected ?string $tagHeader = 'Bailleur Usager Close Signalement';

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
            'NOM_COMPLET_DECLARANT' => $signalement->getPrenomOccupant().' '.$signalement->getNomOccupant(),
            'LINK_DOSSIER_BAILLEUR' => $this->urlGenerator->generate(
                'app_login_bailleur',
                [],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
