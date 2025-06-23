<?php

namespace App\Service\Mailer\Mail\Cron;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CronMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_CRON;
    protected ?string $mailerTemplate = 'cron_email';
    public const MAILER_SUBJECT = 'La tâche planifiée %s.';

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
        return [
            'cron_label' => $notificationMail->getCronLabel(),
            'message' => $notificationMail->getMessage(),
            'count' => $notificationMail->getCronCount(),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            isset($notificationMail->getParams()['error_messages']) && $notificationMail->getParams()['error_messages'] > 0 ?
                's\'est arrêtée en erreur' :
                's\'est bien exécutée'
        );
    }
}
