<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementConfirmInjonctionMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_CONFIRM_INJONCTION_TO_USAGER;
    protected ?string $mailerSubject = 'Votre signalement a bien été reçu ! -- TODO Injonction';
    protected ?string $mailerButtonText = 'Accéder à mon dossier';
    protected ?string $mailerTemplate = 'accuse_injonction_email';
    protected ?string $tagHeader = 'Usager Accusé Reception Signalement Injonction';

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
        $attachment = $notificationMail->getAttachment();

        return [
            'signalement_declarantPrenom' => $signalement->getPrenomOccupant(),
            'signalement_declarantNom' => $signalement->getNomOccupant(),
            'signalement_adresseOccupant' => $signalement->getAdresseOccupant(),
            'signalement_cpOccupant' => $signalement->getCpOccupant(),
            'signalement_villeOccupant' => $signalement->getVilleOccupant(),
            'signalement_isProprioAverti' => $signalement->getIsProprioAverti(),
            'attach' => $attachment,
            'lien_suivi' => $this->urlGenerator->generate(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
