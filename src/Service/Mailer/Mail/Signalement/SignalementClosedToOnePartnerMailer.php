<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Entity\User;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementClosedToOnePartnerMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s - %s] %s a terminé son intervention';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNER;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'closed_to_partner_signalement_email';
    protected ?string $tagHeader = 'Pro Cloture Partenaire';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    /**
     * @return array<mixed>
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        /** @var User $user */
        $user = $this->security->getUser();

        return [
            'ref_signalement' => $signalement->getReference(),
            'partner_name' => $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())?->getNom(),
            'link' => $this->generateLinkSignalementView($signalement->getUuid()),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $signalement = $notificationMail->getSignalement();
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            $signalement->getReference(),
            $signalement->getNomOccupantOrDeclarant(),
            $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())?->getNom(),
        );
    }
}
