<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MassAffectationToPartnerMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT_SINGULAR = '[%s] Un nouveau signalement vous attend.';
    public const MAILER_SUBJECT_PLURAL = '[%s] %d nouveaux signalements vous attendent.';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_MASS_AFFECTATION_TO_PARTNER;
    protected ?string $mailerTemplate = 'mass_affectation_to_partner_email';
    protected ?string $tagHeader = 'Pro Affectation en masse';

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
        $signalements = [];
        foreach ($notificationMail->getParams()['signalements'] as $signalement) {
            $signalements[] = [
                'reference' => $signalement['reference'],
                'link' => $this->generateLinkSignalementView($signalement['uuid']),
            ];
        }

        return ['signalements' => $signalements];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $count = \count($notificationMail->getParams()['signalements']);
        $zip = $notificationMail->getTerritory()?->getZip();

        $this->mailerSubject = $count > 1
            ? \sprintf(self::MAILER_SUBJECT_PLURAL, $zip, $count)
            : \sprintf(self::MAILER_SUBJECT_SINGULAR, $zip);
    }
}
