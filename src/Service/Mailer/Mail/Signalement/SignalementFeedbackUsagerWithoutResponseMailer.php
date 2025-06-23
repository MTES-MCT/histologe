<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementFeedbackUsagerWithoutResponseMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType =
        NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITHOUT_RESPONSE;
    protected ?string $mailerSubject = '%param.platform_name% : faites le point sur votre problème de logement !';
    protected ?string $mailerButtonText = 'Mettre à jour ma situation';
    protected ?string $mailerTemplate = 'demande_feedback_usager_email';
    protected ?string $tagHeader = 'Usager relance sans reponse';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'FEATURE_SUIVI_ACTION')]
        private readonly bool $featureSuiviAction,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    /**
     * @return array<mixed>
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $toRecipient = $notificationMail->getTo();
        // TODO à supprimer avec la suppression du feature flipping featureSuiviAction
        if ($this->featureSuiviAction) {
            $link = $this->generateLink(
                'front_suivi_signalement_messages',
                ['code' => $signalement->getCodeSuivi()]
            );
        } else {
            $link = $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi()]
            );
        }

        return [
            'signalement_adresseOccupant' => $signalement->getAdresseOccupant(),
            'signalement_cpOccupant' => $signalement->getCpOccupant(),
            'signalement_villeOccupant' => $signalement->getVilleOccupant(),
            'signalement_mailOccupant' => $signalement->getMailOccupant(),
            'signalement_nomOccupant' => $signalement->getNomOccupant(),
            'signalement_prenomOccupant' => $signalement->getPrenomOccupant(),
            'signalement_mailDeclarant' => $signalement->getMailDeclarant(),
            'signalement_nomDeclarant' => $signalement->getNomDeclarant(),
            'signalement_prenomDeclarant' => $signalement->getPrenomDeclarant(),
            'from' => $toRecipient,
            'lien_suivi' => $link,
        ];
    }
}
