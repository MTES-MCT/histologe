<?php

namespace App\Service\Mailer\Mail\SignalementDraft;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementDraftPendingBailleurPrevenuMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_DRAFT_PENDING_BAILLEUR_PREVENU;
    protected ?string $mailerSubject = 'Reprendre votre signalement';
    protected ?string $mailerButtonText = 'Reprendre le signalement';
    protected ?string $mailerTemplate = 'signalement_draft_pending_bailleur_prevenu';
    protected ?string $tagHeader = 'Usager Signalement En Cours Bailleur Prevenu';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        protected SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalementDraft = $notificationMail->getSignalementDraft();

        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $this->signalementDraftRequestSerializer->denormalize(
            $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        return [
            'prenomDeclarant' => $signalementDraftRequest->getVosCoordonneesTiersPrenom() ?? $signalementDraftRequest->getVosCoordonneesOccupantPrenom(),
            'dateCreated' => $signalementDraft->getCreatedAt()->format('d/m/Y'),
            'adresseComplete' => $signalementDraft->getAddressComplete(),
            'link' => $this->urlGenerator->generate(
                'front_formulaire_signalement_edit',
                [
                    'uuid' => $signalementDraft->getUuid(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }
}
