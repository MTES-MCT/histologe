<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TempDataLeakDeclarantMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_TEMP_DATA_LEAK_DECLARANT;
    protected ?string $mailerSubject = 'Information importante relative à la sécurité des données partagées sur la plateforme';
    protected ?string $mailerTemplate = 'temp_data_leak_declarant_email';
    protected ?string $tagHeader = 'Declarant Fuite Donnees Occupant';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    /**
     * Les paramètres (nom_declarant, prenom_declarant, signalements) sont construits par la commande
     * et transmis via NotificationMail::$params.
     *
     * @return array<mixed>
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        return [];
    }
}
