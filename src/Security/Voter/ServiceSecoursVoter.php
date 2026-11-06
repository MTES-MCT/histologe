<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\ServiceSecoursRouteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, string>
 */
class ServiceSecoursVoter extends Voter
{
    public const string SERVICE_SECOURS_SEE = 'SERVICE_SECOURS_SEE';

    public function __construct(
        private readonly ServiceSecoursRouteRepository $serviceSecoursRouteRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::SERVICE_SECOURS_SEE === $attribute && !$subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        return match ($attribute) {
            self::SERVICE_SECOURS_SEE => $this->canSeeServiceSecours($user),
            default => false,
        };
    }

    private function canSeeServiceSecours(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isTerritoryAdmin() && $this->serviceSecoursRouteRepository->count(['territory' => $user->getFirstTerritory()]) > 0);
    }
}
