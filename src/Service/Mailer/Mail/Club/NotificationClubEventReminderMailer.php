<?php

namespace App\Service\Mailer\Mail\Club;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationClubEventReminderMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_CLUB_EVENT_REMINDER;
    protected ?string $mailerSubject = 'J-2 ! %s de Signal Logement';
    protected ?string $mailerTemplate = 'club_event_reminder_email';
    protected ?string $tagHeader = 'Pro Club Event Reminder';

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
        $params = $notificationMail->getParams();

        return ['params' => $params];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = \sprintf(
            $this->mailerSubject,
            $notificationMail->getParams()['name']
        );
    }
}
