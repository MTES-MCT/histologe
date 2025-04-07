<?php

namespace App\Factory;

use App\Entity\Affectation;
use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NotificationFactory
{
    public function __construct(
        #[Autowire(env: 'FEATURE_EMAIL_RECAP')]
        private readonly bool $featureEmailRecap,
    ) {
    }

    public function createInstanceFrom(User $user, NotificationType $type, ?Suivi $suivi = null, ?Affectation $affectation = null, ?Signalement $signalement = null): Notification
    {
        $waitMaillingSummary = $this->featureEmailRecap && $user->getIsMailingActive() && $user->getIsMailingSummary();
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
            ->setType(NotificationType::NOUVEAU_SUIVI)
            ->setWaitMaillingSummary($waitMaillingSummary);
    }
}
