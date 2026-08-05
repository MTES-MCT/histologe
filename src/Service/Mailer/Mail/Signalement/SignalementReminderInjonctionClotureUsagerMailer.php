<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementReminderInjonctionClotureUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_REMINDER_CLOTURE_TO_USAGER;
    protected ?string $mailerSubject = 'Votre bailleur indique la fin des travaux concernant votre logement';
    protected ?string $brevoTemplateId = '318';
    protected ?string $tagHeader = 'Usager Rappel Clôture Signalement Injonction';

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
            'ADRESSE_OCCUPANT' => $signalement->getAddress()->getFull(),
            'NOM_COMPLET_PROPRIO' => $signalement->getNomProprio().' '.$signalement->getPrenomProprio(),
            'LINK_SIGNALEMENT_USAGER' => $this->generateLink(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi()]
            ),
        ];
    }
}
