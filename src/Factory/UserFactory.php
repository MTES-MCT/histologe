<?php

namespace App\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserFactory
{
    public function __construct(
        private TokenGeneratorInterface $tokenGenerator,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function createInstanceFrom(
        string $roleLabel,
        ?Partner $partner,
        ?Territory $territory,
        ?string $firstname,
        ?string $lastname,
        ?string $email,
        ?bool $isMailActive = true): User
    {
        return (new User())
            ->setRoles(\in_array($roleLabel, User::ROLES) ? [$roleLabel] : [User::ROLES[$roleLabel]])
            ->setPartner($partner)
            ->setTerritory($territory)
            ->setPrenom($firstname)
            ->setNom($lastname)
            ->setEmail($email)
            ->setStatut(User::STATUS_INACTIVE)
            ->setIsMailingActive($isMailActive)
            ->setPassword($this->tokenGenerator->generateToken())
            ->setToken($this->tokenGenerator->generateToken())
            ->setTokenExpiredAt(
                (new \DateTimeImmutable())->modify($this->parameterBag->get('token_lifetime'))
            );
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
            isMailActive: $data['isMailingActive']
        );
    }
}
