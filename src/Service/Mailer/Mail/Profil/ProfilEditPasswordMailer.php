<?php

namespace App\Service\Mailer\Mail\Profil;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProfilEditPasswordMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_PROFIL_EDIT_PASSWORD;
    protected ?string $mailerSubject = 'Mot de passe mis à jour !';
    protected ?string $mailerTemplate = 'profil_edit_password';

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
        $link = $this->generateLink('back_dashboard', []);

        return [
            'btntext' => 'Me connecter à '.$this->parameterBag->get('platform_name'),
            'link' => $link,
        ];
    }
}
