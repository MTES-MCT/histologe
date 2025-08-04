<?php

namespace App\Factory;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UserFactory
{
    public function __construct(
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private bool $featureNewDashboard,
    ) {
    }

    public function createInstanceFrom(
        string $roleLabel,
        ?string $firstname,
        ?string $lastname,
        ?string $email,
        ?bool $isMailActive = true,
        ?bool $isActivateAccountNotificationEnabled = true,
        ?bool $hasPermissionAffectation = false,
    ): User {
        $user = (new User())
            ->setRoles(\in_array($roleLabel, User::ROLES) ? [$roleLabel] : [User::ROLES[$roleLabel]])
            ->setPrenom($firstname)
            ->setNom($lastname)
            ->setEmail($email)
            ->setStatut(UserStatus::INACTIVE)
            ->setIsMailingActive($isMailActive)
            ->setIsActivateAccountNotificationEnabled($isActivateAccountNotificationEnabled)
            ->setHasPermissionAffectation($hasPermissionAffectation);
        if ($this->featureNewDashboard) {
            $user->setHasDoneSubscriptionsChoice(true);
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createInstanceFromArray(array $data): User
    {
        return $this->createInstanceFrom(
            roleLabel: $data['roles'],
            email: $data['email'],
            lastname: $data['nom'],
            firstname: $data['prenom'],
            isMailActive: $data['isMailingActive'],
            hasPermissionAffectation: $data['hasPermissionAffectation'] ?? false
        );
    }
}
