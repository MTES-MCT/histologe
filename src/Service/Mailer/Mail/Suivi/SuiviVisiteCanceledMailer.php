<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviVisiteCanceledMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_VISITE_CANCELED_TO_USAGER;
    protected ?string $mailerSubject = 'Annulation de la visite de votre logement';
    protected ?string $mailerButtonText = 'Accéder à mon dossier';
    protected ?string $mailerTemplate = 'nouveau_suivi_visite_canceled_email';
    protected ?string $tagHeader = 'Usager Annulation Visite Prevue';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator, $this->entityManager);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $intervention = $notificationMail->getIntervention();

        return [
            'signalement' => $signalement,
            'intervention' => $intervention,
            'lien_suivi' => $this->urlGenerator->generate(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $notificationMail->getTo()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
