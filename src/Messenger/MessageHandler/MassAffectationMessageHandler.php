<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Messenger\Message\MassAffectationMessage;
use App\Repository\PartnerRepository;
use App\Repository\Query\Partner\PartnerLocalizationQuery;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Notification\NotificationAndMailSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MassAffectationMessageHandler
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly UserRepository $userRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerLocalizationQuery $partnerLocalizationQuery,
        private readonly AffectationManager $affectationManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(MassAffectationMessage $message): void
    {
        $this->logger->info('Start handling MassAffectationMessage', [
            'partnerId' => $message->getPartnerId(),
            'userId' => $message->getUserId(),
            'signalementsCount' => count($message->getSignalementIds()),
        ]);

        $partner = $this->partnerRepository->find($message->getPartnerId());
        $user = $this->userRepository->find($message->getUserId());
        if (!$partner || !$user) {
            $this->logger->error('Partner or user not found for mass affectation', [
                'partnerId' => $message->getPartnerId(),
                'userId' => $message->getUserId(),
            ]);

            return;
        }
        if ($partner->getIsArchive()) {
            $this->logger->error('Partner archivé', [
                'partnerId' => $message->getPartnerId(),
            ]);

            return;
        }

        $signalementIdsInPerimeter = $this->partnerLocalizationQuery->findSignalementIdsInPartnerPerimeter($partner, $message->getSignalementIds());
        /** @var Signalement[] $signalements */
        $signalements = $this->signalementRepository->findBy(['id' => $message->getSignalementIds()]);
        $notAffectedSignalementsBadStatus = [];
        $notAffectedSignalementsOutOfPerimeter = [];
        $alreadyAffectedSignalements = [];
        $affectedSignalements = [];
        $affectations = [];
        foreach ($signalements as $signalement) {
            $dataSignalement = [
                'reference' => $signalement->getReference(),
                'uuid' => $signalement->getUuid(),
            ];
            if (SignalementStatus::ACTIVE != $signalement->getStatut()) {
                $notAffectedSignalementsBadStatus[] = $dataSignalement;
                continue;
            }
            if (!in_array($signalement->getId(), $signalementIdsInPerimeter)) {
                $notAffectedSignalementsOutOfPerimeter[] = $dataSignalement;
                continue;
            }
            if ($signalement->getAffectationForPartner($partner)) {
                $alreadyAffectedSignalements[] = $dataSignalement;
                continue;
            }
            $affectation = $this->affectationManager->createAffectation(
                signalement: $signalement,
                partner: $partner,
                user: $user,
                dispatchEvent: false,          // pas de mail unitaire
                dispatchInterconnection: true, // interconnexion déclenchée
            );
            $signalement->addAffectation($affectation);
            $affectedSignalements[] = $dataSignalement;
            $affectations[] = $affectation;
        }
        $this->notificationAndMailSender->sendMassAffectation($partner, $affectations);
        $this->entityManager->flush();
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_MASS_AFFECTATION_REPORT,
                to: $user->getEmail(),
                territory: $partner->getTerritory(),
                params: [
                    'partner' => $partner->getNom(),
                    'affected' => $affectedSignalements,
                    'alreadyAffected' => $alreadyAffectedSignalements,
                    'outOfPerimeter' => $notAffectedSignalementsOutOfPerimeter,
                    'notActive' => $notAffectedSignalementsBadStatus,
                ]
            )
        );
        $this->logger->info('MassAffectationMessage handled successfully', [
            'partnerId' => $message->getPartnerId(),
        ]);
    }
}
