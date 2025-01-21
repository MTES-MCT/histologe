<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AffectationVoter extends Voter
{
    public const SEE = 'AFFECTATION_SEE';
    public const TOGGLE = 'AFFECTATION_TOGGLE';
    public const ANSWER = 'AFFECTATION_ANSWER';
    public const CLOSE = 'AFFECTATION_CLOSE';
    public const REOPEN = 'AFFECTATION_REOPEN';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SEE, self::TOGGLE, self::ANSWER, self::CLOSE, self::REOPEN])
            && ($subject instanceof Affectation || $subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface || !$user->isSuperAdmin() && !$user->hasPartnerInTerritory($subject->getTerritory())) {
            return false;
        }

        return match ($attribute) {
            self::SEE => $this->canSee($subject, $user),
            self::TOGGLE => $this->canToggle($subject, $user),
            self::ANSWER => $this->canAnswer($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::REOPEN => $this->canReopen($subject, $user),
            default => false,
        };
    }

    private function canSee(Signalement $signalement, User $user)
    {
        return (
            $user->isSuperAdmin()
            || $user->isTerritoryAdmin()
            || $user->hasPermissionAffectation()
        )
            && Signalement::STATUS_NEED_VALIDATION !== $signalement->getStatut();
    }

    private function canToggle(Signalement $signalement, User $user)
    {
        return (
            $user->isSuperAdmin()
            || $user->isTerritoryAdmin()
            || $user->hasPermissionAffectation()
        )
            && Signalement::STATUS_ACTIVE === $signalement->getStatut();
    }

    private function canAnswer(Affectation $affectation, User $user): bool
    {
        return $affectation->getPartner() === $user->getPartnerInTerritory($affectation->getSignalement()->getTerritory()) && Signalement::STATUS_ACTIVE === $affectation->getSignalement()->getStatut();
    }

    private function canClose(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && Affectation::STATUS_ACCEPTED === $affectation->getStatut();
    }

    private function canReopen(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && Affectation::STATUS_CLOSED === $affectation->getStatut();
    }
}
