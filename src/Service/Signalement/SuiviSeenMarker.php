<?php

namespace App\Service\Signalement;

use App\Dto\NotificationSuiviUser;
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
        $suiviLastUserSeen = $this->getLastSeenSuivi($signalement);

        $lastSeenDate = $suiviLastUserSeen && $suiviLastUserSeen->isSeen ? $suiviLastUserSeen->seenAt : null;
        foreach ($signalement->getSuivis() as $suivi) {
            if (!$suivi->getIsPublic()) {
                continue;
            }

            $isSeen = null !== $lastSeenDate && $suivi->getCreatedAt() <= $lastSeenDate;
            $suivi->setSeenByUsager($isSeen);
        }
    }

    private function getLastSeenSuivi(Signalement $signalement): ?NotificationSuiviUser
    {
        $notificationsSuiviUser = $this->notificationRepository->getNotificationsFrom($signalement);
        foreach ($notificationsSuiviUser as $notificationSuiviUser) {
            if ($notificationSuiviUser->isSeen) {
                return $notificationSuiviUser;
            }
        }

        return null;
    }
}
