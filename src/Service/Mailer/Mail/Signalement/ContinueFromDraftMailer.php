<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContinueFromDraftMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_CONTINUE_FROM_DRAFT_TO_USAGER;
    protected ?string $mailerSubject = 'Complétez votre signalement sur %param.platform_name%.';
    protected ?string $mailerButtonText = 'Compléter mon signalement';
    protected ?string $mailerTemplate = 'continue_from_draft_email';
    protected ?string $tagHeader = 'Usager Validation Signalement';

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
        $signalementDraft = $notificationMail->getSignalementDraft();

        return [
            'signalement_draft_createdAt' => $signalementDraft->getCreatedAt()->format('d/m/Y'),
            'signalement_draft_addressComplete' => $signalementDraft->getAddressComplete(),
            'lien_draft' => $this->urlGenerator->generate(
                'front_formulaire_signalement_edit',
                [
                    'uuid' => $signalementDraft->getUuid(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
