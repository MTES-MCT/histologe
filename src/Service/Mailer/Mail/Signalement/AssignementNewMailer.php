<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssignementNewMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_ASSIGNMENT_NEW;
    protected ?string $mailerSubject = 'Un nouveau signalement vous attend sur %param.platform_name%.';
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'affectation_email';

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
}
