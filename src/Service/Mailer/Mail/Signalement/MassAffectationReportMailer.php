<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Bilan de l'affectation en masse envoyé au super admin qui l'a lancée.
 *
 * Params attendus dans le NotificationMail (scalaires uniquement) :
 * - partner : nom du partenaire
 * - affected, alreadyAffected, outOfPerimeter, notActive : listes de ['reference' => string, 'uuid' => string]
 */
class MassAffectationReportMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = 'Affectation en masse vers %s terminée';
    public const SIGNALEMENT_LISTS = ['affected', 'alreadyAffected', 'outOfPerimeter', 'notActive'];
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_MASS_AFFECTATION_REPORT;
    protected ?string $mailerTemplate = 'mass_affectation_report_email';
    protected ?string $tagHeader = 'Pro Bilan affectation en masse';

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
        $params = $notificationMail->getParams();
        $mailerParams = ['partner' => $params['partner'] ?? ''];
        foreach (self::SIGNALEMENT_LISTS as $list) {
            $mailerParams[$list] = [];
            foreach ($params[$list] ?? [] as $signalement) {
                $mailerParams[$list][] = [
                    'reference' => $signalement['reference'],
                    'link' => $this->generateLinkSignalementView($signalement['uuid']),
                ];
            }
        }

        return $mailerParams;
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = \sprintf(self::MAILER_SUBJECT, $notificationMail->getParams()['partner'] ?? '');
    }
}
