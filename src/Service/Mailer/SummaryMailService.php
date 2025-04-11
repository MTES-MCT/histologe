<?php

namespace App\Service\Mailer;

use App\Entity\Enum\NotificationType;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class SummaryMailService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
    }

    public function sendSummaryEmailIfNeeded(User $user): int
    {
        $events = [
            NotificationType::NOUVEAU_SIGNALEMENT->name => [],
            NotificationType::NOUVEAU_SUIVI->name => [],
            NotificationType::NOUVELLE_AFFECTATION->name => [],
            NotificationType::CLOTURE_SIGNALEMENT->name => [],
            NotificationType::CLOTURE_PARTENAIRE->name => [],
        ];
        $isNotifiable = $user->getIsMailingActive() && $user->getIsMailingSummary();
        $dateTime = new \DateTimeImmutable();
        $notifications = $this->notificationRepository->findBy(['user' => $user, 'waitMailingSummary' => true], ['createdAt' => 'DESC']);
        foreach ($notifications as $notification) {
            $notification->setWaitMailingSummary(false);
        }
        if (!$isNotifiable) {
            $this->entityManager->flush();

            return 0;
        }

        foreach ($notifications as $notification) {
            $notification->setMailingSummarySentAt($dateTime);
            switch ($notification->getType()) {
                case NotificationType::NOUVEAU_SIGNALEMENT:
                case NotificationType::NOUVELLE_AFFECTATION:
                case NotificationType::CLOTURE_SIGNALEMENT:
                    $events[$notification->getType()->name][$notification->getSignalement()->getId()] = [
                        'uuid' => $notification->getSignalement()->getUuid(),
                        'reference' => $notification->getSignalement()->getReference(),
                    ];
                    break;
                case NotificationType::NOUVEAU_SUIVI:
                    if (!isset($events[$notification->getType()->name][$notification->getSignalement()->getId()])) {
                        $events[$notification->getType()->name][$notification->getSignalement()->getId()] = [
                            'uuid' => $notification->getSignalement()->getUuid(),
                            'reference' => $notification->getSignalement()->getReference(),
                            'nb' => 0,
                        ];
                    }
                    ++$events[$notification->getType()->name][$notification->getSignalement()->getId()]['nb'];
                    break;
                case NotificationType::CLOTURE_PARTENAIRE:
                    $events[$notification->getType()->name][$notification->getSignalement()->getId()] = [
                        'uuid' => $notification->getSignalement()->getUuid(),
                        'reference' => $notification->getSignalement()->getReference(),
                        'partenaire' => $notification->getAffectation()->getPartner()->getNom(),
                    ];
                    break;
            }
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_NOTIFICATIONS_SUMMARY,
                to: $user->getEmail(),
                params: $events,
            )
        );

        $this->entityManager->flush();

        return 1;
    }
}
