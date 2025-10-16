<?php

namespace App\Security\Provider;

use App\Repository\SignalementRepository;
use App\Security\User\SignalementBailleur;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SignalementBailleurProvider implements UserProviderInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $signalementUuid): UserInterface
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => $signalementUuid]);

        if (!$signalement) {
            throw new UserNotFoundException('Signalement introuvable.');
        }

        return new SignalementBailleur($signalementUuid);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SignalementBailleur) {
            throw new \InvalidArgumentException(sprintf('Instances de "%s" non supportées.', $user::class));
        }

        $identifier = $user->getUserIdentifier();

        return $this->loadUserByIdentifier($identifier);
    }

    public function supportsClass(string $class): bool
    {
        return SignalementBailleur::class === $class;
    }
}
