<?php

namespace App\Manager;

use App\Entity\Partner;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class UserManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = User::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function updateUserFromData(User $user, array $data): User
    {
        $user
            ->setNom($data['nom'])
            ->setPrenom($data['prenom'])
            ->setRoles([$data['roles']])
            ->setEmail($data['email'])
            ->setIsGenerique($data['isGenerique'])
            ->setIsMailingActive($data['isMailingActive']);

        return $user;
    }

    public function getUserFrom(Partner $partner, int $userId): ?User
    {
        return $this->getRepository()->findOneBy(['partner' => $partner, 'id' => $userId]);
    }
}
