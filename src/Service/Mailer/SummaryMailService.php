<?php

namespace App\Service\Mailer;

use App\Entity\Enum\NotificationType;
use App\Entity\User;
use App\Repository\NotificationRepository;

class SummaryMailService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
    }

    public function sendSummaryEmailIfNeeded(User $user): int
    {
        $isNotifiable = $user->getIsMailingActive() && $user->getIsMailingSummary();
        $notifications = $this->notificationRepository->findWaitingSummaryForUser($user);
        if (!$isNotifiable) {
            $this->notificationRepository->massUpdate($notifications, ['waitMailingSummary' => false]);

            return 0;
        }
        $events = $this->getEventsForMailingSummaryFromNotifications($notifications);
        $hasEvents = (bool) array_filter($events, fn ($sub) => !empty($sub));

        if (!$hasEvents) {
            return 0;
        }
        $now = new \DateTimeImmutable();
        $this->notificationRepository->massUpdate($notifications, ['waitMailingSummary' => false, 'mailingSummarySentAt' => $now]);
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_NOTIFICATIONS_SUMMARY,
                to: $user->getEmail(),
                params: $events,
            )
        );

        return 1;
    }

    /**
     * @param array<mixed> $notifications
     *
     * @return array<mixed>
     */
    private function getEventsForMailingSummaryFromNotifications(array $notifications): array
    {
        $events = [
            NotificationType::NOUVEAU_SIGNALEMENT->name => [],
            NotificationType::NOUVEAU_SUIVI->name => [],
            NotificationType::NOUVEL_ABONNEMENT->name => [],
            NotificationType::NOUVELLE_AFFECTATION->name => [],
            NotificationType::CLOTURE_SIGNALEMENT->name => [],
            NotificationType::CLOTURE_PARTENAIRE->name => [],
        ];
        foreach ($notifications as $notification) {
            $notificationType = $notification->getType()->name;
            switch ($notification->getType()) {
                case NotificationType::NOUVEL_ABONNEMENT: // voir si c'est ici
                case NotificationType::NOUVEAU_SIGNALEMENT:
                case NotificationType::NOUVELLE_AFFECTATION:
                case NotificationType::CLOTURE_SIGNALEMENT:
                    $events[$notificationType][$notification->getSignalement()->getId()] = [
                        'uuid' => $notification->getSignalement()->getUuid(),
                        'reference' => $notification->getSignalement()->getReference(),
                    ];
                    break;
                case NotificationType::NOUVEAU_SUIVI:
                    if (!isset($events[$notificationType][$notification->getSignalement()->getId()])) {
                        $events[$notificationType][$notification->getSignalement()->getId()] = [
                            'uuid' => $notification->getSignalement()->getUuid(),
                            'reference' => $notification->getSignalement()->getReference(),
                            'nb' => 0,
                        ];
                    }
                    ++$events[$notificationType][$notification->getSignalement()->getId()]['nb'];
                    break;
                case NotificationType::CLOTURE_PARTENAIRE:
                    $events[$notificationType][$notification->getSignalement()->getId()] = [
                        'uuid' => $notification->getSignalement()->getUuid(),
                        'reference' => $notification->getSignalement()->getReference(),
                        'partenaire' => $notification->getAffectation()->getPartner()->getNom(),
                    ];
                    break;
            }
        }

        return $events;
    }
}
