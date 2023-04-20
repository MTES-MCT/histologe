<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteRescheduledToUsagerMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER;
    protected ?string $mailerSubject = 'Modification de la date de visite de votre logement';
    protected ?string $mailerButtonText = 'Accéder à mon signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_rescheduled_email';

    /*La visite du logement initialement prévue le {{ancienne date}} a été décalée au {{nouvelle date}}.
La visite sera effectuée par {{nom partenaire opérateur}}.

Si vous n'êtes pas disponible à cette date, veuillez nous le signaler au plus vite en cliquant sur le bouton ci-dessous.
    */

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
        $intervention = $notificationMail->getIntervention();
        $previousDate = $notificationMail->getPreviousVisiteDate();

        return [
            'signalement' => $signalement,
            'intervention' => $intervention,
            'old_date' => $previousDate,
            'lien_suivi' => $this->urlGenerator->generate(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $notificationMail->getTo()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
