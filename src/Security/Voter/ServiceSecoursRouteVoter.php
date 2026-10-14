<?php

namespace App\Security\Voter;

use App\Entity\ServiceSecoursRoute;
use App\Security\User\ServiceSecoursUser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ServiceSecoursRoute>
 */
class ServiceSecoursRouteVoter extends Voter
{
    public const string ACCESS = 'SERVICE_SECOURS_ROUTE_ACCESS';

    public function __construct(
        #[Autowire(env: 'FEATURE_SERVICE_SECOURS_SECURITY')]
        private readonly bool $isSecurityEnabled,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof ServiceSecoursRoute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$this->isSecurityEnabled) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof ServiceSecoursUser) {
            $vote?->addReason('L\'agent du service de secours n\'est pas authentifié');

            return false;
        }

        /** @var ServiceSecoursRoute $serviceSecoursRoute */
        $serviceSecoursRoute = $subject;

        if ($user->getServiceSecoursRouteUuid() !== (string) $serviceSecoursRoute->getUuid()) {
            $vote?->addReason('Vous n\'êtes pas autorisé à accéder à ce service de secours.');

            return false;
        }

        return true;
    }
}
