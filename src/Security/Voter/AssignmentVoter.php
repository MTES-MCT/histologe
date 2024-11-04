<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AssignmentVoter extends Voter
{
    public const SEE = 'ASSIGN_SEE';
    public const TOGGLE = 'ASSIGN_TOGGLE';
    public const ANSWER = 'ASSIGN_ANSWER';
    public const CLOSE = 'ASSIGN_CLOSE';
    public const REOPEN = 'ASSIGN_REOPEN';

    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SEE, self::TOGGLE, self::ANSWER, self::CLOSE, self::REOPEN])
            && ($subject instanceof Affectation || $subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface || !$user->isSuperAdmin() && $subject->getTerritory() !== $user->getPartner()?->getTerritory()) {
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
            || ($this->parameterBag->get('feature_permission_affectation') && $user->hasPermissionAffectation())
        )
            && Signalement::STATUS_NEED_VALIDATION !== $signalement->getStatut();
    }

    private function canToggle(Signalement $signalement, User $user)
    {
        return (
            $user->isSuperAdmin()
            || $user->isTerritoryAdmin()
            || ($this->parameterBag->get('feature_permission_affectation') && $user->hasPermissionAffectation())
        )
            && Signalement::STATUS_ACTIVE === $signalement->getStatut();
    }

    private function canAnswer(Affectation $assignment, User $user): bool
    {
        return $assignment->getPartner() === $user->getPartner() && Signalement::STATUS_ACTIVE === $assignment->getSignalement()->getStatut();
    }

    private function canClose(Affectation $assignment, User $user): bool
    {
        return $this->canAnswer($assignment, $user) && Affectation::STATUS_ACCEPTED === $assignment->getStatut();
    }

    private function canReopen(Affectation $assignment, User $user): bool
    {
        return $this->canAnswer($assignment, $user) && Affectation::STATUS_CLOSED === $assignment->getStatut();
    }
}
