<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssignementNewMailer extends AbstractNotificationMailer
{
    public const MAILER_SUBJECT = '[%s] Un nouveau signalement vous attend.';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ASSIGNMENT_NEW;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'affectation_email';
    protected ?string $tagHeader = 'Pro Nouvelle affectation';

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
        $signalement = $notificationMail->getSignalement();
        $uuid = $signalement->getUuid();

        return [
            'ref_signalement' => $signalement->getReference(),
            'link' => $this->urlGenerator->generate(
                'back_signalement_view',
                ['uuid' => $uuid],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
        $this->mailerSubject = \sprintf(
            self::MAILER_SUBJECT,
            $notificationMail->getSignalement()->getCpOccupant()
        );
    }
}
