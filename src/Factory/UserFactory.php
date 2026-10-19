<?php

namespace App\Factory;

use App\Entity\Enum\UserStatus;
use App\Entity\User;

class UserFactory
{
    public function createInstanceFrom(
        string $roleLabel,
        ?string $firstname,
        ?string $lastname,
        ?string $email,
        ?bool $isMailActive = true,
        ?bool $isActivateAccountNotificationEnabled = true,
        ?bool $hasPermissionAffectation = false,
        ?string $phone = null,
        ?string $fonction = null,
    ): User {
        $user = (new User())
            ->setRoles(\in_array($roleLabel, User::ROLES) ? [$roleLabel] : [User::ROLES[$roleLabel]])
            ->setPrenom($firstname)
            ->setNom($lastname)
            ->setEmail($email)
            ->setStatut(UserStatus::INACTIVE)
            ->setIsMailingActive($isMailActive)
            ->setIsActivateAccountNotificationEnabled($isActivateAccountNotificationEnabled)
            ->setHasPermissionAffectation($hasPermissionAffectation)
            ->setPhone($phone)
            ->setFonction($fonction);

        return $user;
    }
}
