<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviVoter extends Voter
{
    public const CREATE = 'COMMENT_CREATE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE])
            && ($subject instanceof Suivi || $subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (
            !$user instanceof UserInterface
            || !$user->isSuperAdmin()
            && $subject->getTerritory() !== $user->getTerritory()
        ) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($subject, $user),
            default => false,
        };
    }

    private function canCreate(Signalement $signalement, User $user): bool
    {
        $isUserInAcceptedAffectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId()
                && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
        })->count() > 0;

        return Signalement::STATUS_ACTIVE === $signalement->getStatut()
            && ($isUserInAcceptedAffectation || $user->isTerritoryAdmin() || $user->isSuperAdmin());
    }
}
