<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementFeedbackUsagerWithResponseMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType =
        NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITH_RESPONSE;
    protected ?string $mailerSubject = '%param.platform_name% : faites le point sur votre problème de logement !';
    protected ?string $mailerButtonText = 'Mettre à jour ma situation';
    protected ?string $mailerTemplate = 'demande_feedback_usager_email';
    protected ?string $tagHeader = 'Usager relance apres reponse';

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
        $toRecipient = $notificationMail->getTo();

        return [
            'signalement' => $signalement,
            'from' => $toRecipient,
            'lien_suivi' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $toRecipient]
            ),
        ];
    }
}
