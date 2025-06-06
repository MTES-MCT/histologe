<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Entity\Suivi;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementFeedbackUsagerThirdMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD;
    protected ?string $mailerSubject = '%param.platform_name% : faites le point sur votre problème de logement !';
    protected ?string $mailerTemplate = 'demande_feedback_usager_third_email';
    protected ?string $tagHeader = 'Usager 3e relance';

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

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $toRecipient = $notificationMail->getTo();
        // TODO à supprimer avec la suppression du feature flipping featureSuiviAction
        if ($this->featureSuiviAction) {
            $linkPoursuivre = $this->generateLink(
                'front_suivi_signalement_procedure_poursuite',
                ['code' => $signalement->getCodeSuivi()]
            );
            $linkArreter = $this->generateLink(
                'front_suivi_signalement_procedure',
                ['code' => $signalement->getCodeSuivi()]
            );
        } else {
            $linkPoursuivre = $this->generateLink(
                'front_suivi_procedure', [
                    'code' => $signalement->getCodeSuivi(),
                    'from' => $toRecipient,
                    'suiviAuto' => Suivi::POURSUIVRE_PROCEDURE,
                ]
            );
            $linkArreter = $this->generateLink(
                'front_suivi_procedure', [
                    'code' => $signalement->getCodeSuivi(),
                    'from' => $toRecipient,
                    'suiviAuto' => Suivi::ARRET_PROCEDURE,
                ]
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
            'lien_suivi_poursuivre' => $linkPoursuivre,
            'lien_suivi_arreter' => $linkArreter,
        ];
    }
}
