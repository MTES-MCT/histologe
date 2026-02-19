<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementInviteTiersMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_INVITE_TIERS;
    protected ?string $mailerSubject = 'Invitation à suivre un dossier de signalement';
    protected ?string $mailerTemplate = 'invite_tiers_email';
    protected ?string $tagHeader = 'Usager Invitation Tiers';

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
        $token = $notificationMail->getTiersInvitation()->getToken();
        $linkAccepter = $this->generateLink(
            'front_suivi_invitation_accepter',
            ['code' => $signalement->getCodeSuivi(), 'token' => $token]
        );
        $linkRefuser = $this->generateLink(
            'front_suivi_invitation_refuser',
            ['code' => $signalement->getCodeSuivi(), 'token' => $token]
        );

        return [
            'signalement_prenomOccupant' => $signalement->getPrenomOccupant(),
            'signalement_nomOccupant' => $signalement->getNomOccupant(),
            'signalement_adresseOccupant' => $signalement->getAdresseOccupant(),
            'signalement_cpOccupant' => $signalement->getCpOccupant(),
            'signalement_villeOccupant' => $signalement->getVilleOccupant(),
            'lien_accepter_invitation' => $linkAccepter,
            'lien_refuser_invitation' => $linkRefuser,
        ];
    }
}
