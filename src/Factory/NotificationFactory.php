<?php

namespace App\Factory;

use App\Entity\Affectation;
use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;

class NotificationFactory
{
    public function createInstanceFrom(
        User $user,
        NotificationType $type,
        ?Suivi $suivi = null,
        ?Affectation $affectation = null,
        ?Signalement $signalement = null,
        ?string $description = null,
    ): Notification {
        if ($suivi) {
            $signalement = $suivi->getSignalement();
        } elseif ($affectation) {
            $signalement = $affectation->getSignalement();
        }

        return (new Notification())
            ->setUser($user)
            ->setSuivi($suivi)
            ->setAffectation($affectation)
            ->setSignalement($signalement)
            ->setType($type)
            ->setDescription($description)
            ->setWaitMailingSummary($this->shouldWaitMailingSummary($user, $type));
    }

    private function shouldWaitMailingSummary(User $user, NotificationType $type): bool
    {
        return $user->getIsMailingActive() && $user->getIsMailingSummary() && in_array($type, NotificationType::getForAgent());
    }
}
