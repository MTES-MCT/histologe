<?php

namespace App\Security;

use App\Repository\SignalementRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SignalementUserProvider implements UserProviderInterface
{
    public function __construct(
        private SignalementRepository $signalementRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $signalement = $this->signalementRepository->findOneByCodeForPublic($identifier, false);

        if (!$signalement) {
            throw new UserNotFoundException(sprintf('Signalement avec code "%s" non trouvé.', $identifier));
        }

        return new SignalementUser($identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SignalementUser) {
            throw new \InvalidArgumentException(sprintf('Instances de "%s" non supportées.', $user::class));
        }

        $identifier = $user->getUserIdentifier();

        return $this->loadUserByIdentifier($identifier);
    }

    public function supportsClass(string $class): bool
    {
        return SignalementUser::class === $class;
    }
}
