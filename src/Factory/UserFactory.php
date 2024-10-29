<?php

namespace App\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;

class UserFactory
{
    public function createInstanceFrom(
        string $roleLabel,
        ?Partner $partner,
        ?Territory $territory,
        ?string $firstname,
        ?string $lastname,
        ?string $email,
        ?bool $isMailActive = true,
        ?bool $isActivateAccountNotificationEnabled = true,
        ?bool $hasPermissionAffectation = false,
    ): User {
        return (new User())
            ->setRoles(\in_array($roleLabel, User::ROLES) ? [$roleLabel] : [User::ROLES[$roleLabel]])
            ->setPartner($partner)
            ->setTerritory($territory)
            ->setPrenom($firstname)
            ->setNom($lastname)
            ->setEmail($email)
            ->setStatut(User::STATUS_INACTIVE)
            ->setIsMailingActive($isMailActive)
            ->setIsActivateAccountNotificationEnabled($isActivateAccountNotificationEnabled)
            ->setHasPermissionAffectation($hasPermissionAffectation);
    }

    public function createInstanceFromArray(Partner $partner, array $data): User
    {
        return $this->createInstanceFrom(
            roleLabel: $data['roles'],
            partner: $partner,
            territory: $partner->getTerritory(),
            email: $data['email'],
            lastname: $data['nom'],
            firstname: $data['prenom'],
            isMailActive: $data['isMailingActive'],
            hasPermissionAffectation: $data['hasPermissionAffectation'] ?? false
        );
    }
}
