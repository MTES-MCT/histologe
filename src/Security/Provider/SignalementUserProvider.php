<?php

namespace App\Security\Provider;

use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Security\User\SignalementUser;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SignalementUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        [$codeSuivi, $type] = explode(':', $identifier);
        $signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi);

        if (!$signalement) {
            throw new UserNotFoundException(sprintf('Signalement avec code "%s" non trouvé.', $identifier));
        }

        $usagerData = $this->getUsagerData($signalement, $type, $codeSuivi);

        return new SignalementUser(
            $usagerData['identifier'],
            $usagerData['email'],
            $usagerData['user'],
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SignalementUser) {
            throw new \InvalidArgumentException(sprintf('Instances de "%s" non supportées.', $user::class));
        }

        $identifier = $user->getUserIdentifier();

        return $this->loadUserByIdentifier($identifier);
    }

    /**
     * @return array{
     *      identifier: string,
     *      email: string|null,
     *      user: User|null,
     *  }
     *
     * @throws NonUniqueResultException
     */
    public function getUsagerData(Signalement $signalement, string $type, string $codeSuivi): array
    {
        if (UserManager::DECLARANT === $type) {
            $user = $this->userRepository->findOneBy(['email' => $signalement->getMailDeclarant()]);

            return [
                'identifier' => $codeSuivi.':'.UserManager::DECLARANT,
                'email' => $signalement->getMailDeclarant(),
                'user' => $user,
            ];
        }
        $user = $this->userRepository->findOneBy(['email' => $signalement->getMailOccupant()]);

        return [
            'identifier' => $codeSuivi.':'.UserManager::OCCUPANT,
            'email' => $signalement->getMailOccupant(),
            'user' => $user,
        ];
    }

    public function supportsClass(string $class): bool
    {
        return SignalementUser::class === $class;
    }
}
