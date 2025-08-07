<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Repository\NotificationRepository;

readonly class SuiviSeenMarker
{
    public function __construct(
        private NotificationRepository $notificationRepository,
    ) {
    }

    public function markSeenByUsager(Signalement $signalement): void
    {
        $lastSeenDate = $this->getLastSeenAt($signalement);

        foreach ($signalement->getSuivis() as $suivi) {
            if (!$suivi->getIsPublic()) {
                continue;
            }

            $isSeen = null !== $lastSeenDate && $suivi->getCreatedAt() <= $lastSeenDate;
            $suivi->setSeenByUsager($isSeen);
        }
    }

    private function getLastSeenAt(Signalement $signalement): ?\DateTimeImmutable
    {
        $notificationsSuiviUser = $this->notificationRepository->getNotificationsFrom($signalement);
        foreach ($notificationsSuiviUser as $notificationSuiviUser) {
            if ($notificationSuiviUser->isSeen) {
                return $notificationSuiviUser->seenAt;
            }
        }

        return $this->getLastUsagerReplyDate($signalement);
    }

    private function getLastUsagerReplyDate(Signalement $signalement): ?\DateTimeImmutable
    {
        $lastDate = null;

        foreach ($signalement->getSuivis() as $suivi) {
            $createdBy = $suivi->getCreatedBy();
            if (null === $createdBy) {
                continue;
            }
            if (in_array($createdBy->getId(), $signalement->getUsagerIds())) {
                $createdAt = $suivi->getCreatedAt();
                if (null === $lastDate || $createdAt > $lastDate) {
                    $lastDate = $createdAt;
                }
            }
        }

        return $lastDate;
    }
}
