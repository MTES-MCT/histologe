<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AskTravauxMiseEnConformiteMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ASK_TRAVAUX_MISE_EN_CONFORMITE;
    protected ?string $mailerSubject = 'Avancement des travaux dans votre logement';
    protected ?string $mailerTemplate = 'ask_travaux_mise_en_conformite_email';
    protected ?string $tagHeader = 'Usager Demande Avancement Des Travaux';

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
        $btn_link = $this->urlGenerator->generate('front_suivi_signalement_complete_travaux_mise_en_conformite', ['code' => $signalement->getCodeSuivi()], UrlGeneratorInterface::ABSOLUTE_URL);

        return [
            'date_cloture' => $signalement->getClosedAt()?->format('d/m/Y'),
            'btn_link' => $btn_link,
        ];
    }
}
