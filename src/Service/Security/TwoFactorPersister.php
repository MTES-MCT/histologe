<?php

namespace App\Service\Security;

use App\Security\User\SignalementUser;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * Remplace le DoctrinePersister de scheb : un SignalementUser n'est pas une entité,
 * le code 2FA est stocké sur l'entité User qu'il encapsule.
 */
class TwoFactorPersister implements PersisterInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function persist(object $user): void
    {
        if ($user instanceof SignalementUser) {
            $user = $user->getUser();
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
