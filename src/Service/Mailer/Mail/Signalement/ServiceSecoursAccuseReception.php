<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ServiceSecoursAccuseReception extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = 'Accusé de réception de dépôt du signalement %s';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SERVICE_SECOURS_ACCUSE_RECEPTION;
    protected ?string $mailerTemplate = 'service_secours_accuse_reception_email';
    protected ?string $tagHeader = 'Service Secours Accusé Reception Signalement';

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
        $attachment = $notificationMail->getAttachment();

        return [
            'reference' => $signalement->getReference(),
            'adresse_logement' => $signalement->getAddressCompleteOccupant(),
            'matricule_declarant' => $signalement->getMatriculeDeclarant(),
            'date_depot' => $signalement->getCreatedAt()->format('d/m/Y H:i'),
            'attachContent' => [
                'content' => $attachment,
                'filename' => 'signalement-'.$signalement->getReference().'.pdf',
            ],
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = \sprintf(self::MAILER_SUBJECT, $notificationMail->getSignalement()->getReference());
    }
}
