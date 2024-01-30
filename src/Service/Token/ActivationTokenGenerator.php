<?php

namespace App\Service\Token;

use App\Entity\User;
use App\Repository\UserRepository;

class ActivationTokenGenerator extends AbstractTokenGenerator
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function validateToken(User $user, string $token): bool|User
    {
        $user = $this->userRepository->findOneBy(['id' => $user->getId(), 'token' => $token]);

        if ($this->canActivateAccount($user) || $this->canUpdatePassword($user)) {
            return $user;
        }

        return false;
    }

    private function canActivateAccount(?User $user): bool
    {
        return null !== $user
            && new \DateTimeImmutable() < $user->getTokenExpiredAt()
            && User::STATUS_INACTIVE === $user->getStatut();
    }

    private function canUpdatePassword(?User $user): bool
    {
        return null !== $user
            && new \DateTimeImmutable() < $user->getTokenExpiredAt()
            && User::STATUS_ACTIVE === $user->getStatut();
    }
}
