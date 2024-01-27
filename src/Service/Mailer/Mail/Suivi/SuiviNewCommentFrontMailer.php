<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviNewCommentFrontMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_NEW_COMMENT_FRONT;
    protected ?string $mailerSubject = 'Nouvelle mise à jour de votre signalement !';
    protected ?string $mailerButtonText = 'Accéder à mon signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_signalement_email';
    protected ?string $tagHeader = 'Usager Nouveau Suivi Signalement';

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

        return [
            'signalement' => $signalement,
            'lien_suivi' => $this->urlGenerator->generate(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $notificationMail->getTo()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
