<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviNewCommentBackMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] Nouveau suivi';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_NEW_COMMENT_BACK;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_signalement_back_email';
    protected ?string $tagHeader = 'Pro Nouveau Suivi Signalement';

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
        $suivi = $notificationMail->getSuivi();
        if ($suivi && $suivi->getCreatedBy()) {
            $suiviCreator = $suivi->getCreatedBy()->getNom().' ('.$suivi->getCreatedBy()->getPartner()->getNom().')';
        } else {
            $suiviCreator = $signalement->getPrenomDeclarant().' '.$signalement->getNomDeclarant();
        }

        return array_merge($notificationMail->getParams(), [
            'signalement_reference' => $signalement->getReference(),
            'suivi_createdor' => $suiviCreator,
            'ref_signalement' => $signalement->getReference(),
            'link' => $this->generateLinkSignalementView($signalement->getUuid()),
        ]);
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $signalement = $notificationMail->getSignalement();
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            $signalement->getReference(),
            $signalement->getNomOccupantOrDeclarant()
        );
    }
}
