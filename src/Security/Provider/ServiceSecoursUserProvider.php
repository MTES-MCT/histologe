<?php

namespace App\Security\Provider;

use App\Repository\ServiceSecoursRouteRepository;
use App\Security\User\ServiceSecoursUser;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<ServiceSecoursUser>
 */
class ServiceSecoursUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly ServiceSecoursRouteRepository $serviceSecoursRouteRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new UserNotFoundException('Le chargement d\'un utilisateur Service Secours par identifiant n\'est pas supporté (utilisateur ProConnect uniquement).');
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof ServiceSecoursUser) {
            throw new \InvalidArgumentException(sprintf('Instances de "%s" non supportées.', $user::class));
        }

        $serviceSecoursRoute = $this->serviceSecoursRouteRepository->findOneBy([
            'uuid' => $user->getServiceSecoursRouteUuid(),
        ]);

        if (!$serviceSecoursRoute) {
            throw new UserNotFoundException(sprintf('ServiceSecoursRoute avec UUID "%s" non trouvée.', $user->getServiceSecoursRouteUuid()));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return ServiceSecoursUser::class === $class;
    }
}
