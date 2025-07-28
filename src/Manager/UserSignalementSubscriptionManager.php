<?php

namespace App\Manager;

use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use Doctrine\Persistence\ManagerRegistry;

class UserSignalementSubscriptionManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = UserSignalementSubscription::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrGet(
        User $userToSubscribe,
        Signalement $signalement,
        User $createdBy,
    ): UserSignalementSubscription {
        $subscription = $this->findOneBy(['user' => $userToSubscribe, 'signalement' => $signalement]);
        if (null === $subscription) {
            $subscription = (new UserSignalementSubscription())
            ->setUser($userToSubscribe)
            ->setSignalement($signalement)
            ->setCreatedBy($createdBy);

            $this->persist($subscription);
        }

        return $subscription;
    }
}
