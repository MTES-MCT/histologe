<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementClosedToUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_USAGER;
    protected ?string $mailerSubject = 'Votre signalement sur %param.platform_name% est terminé.';
    protected ?string $mailerButtonText = 'Accéder à mon signalement';
    protected ?string $mailerTemplate = 'closed_to_usager_signalement_email';
    protected ?string $tagHeader = 'Usager Cloture Signalement';

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
            'motif_cloture' => $signalement->getMotifCloture()->label(),
            'link' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $toRecipient]
            ),
        ];
    }
}
