<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AssignmentVoter extends Voter
{
    public const TOGGLE = 'ASSIGN_TOGGLE';
    public const ANSWER = 'ASSIGN_ANSWER';
    public const CLOSE = 'ASSIGN_CLOSE';
    public const REOPEN = 'ASSIGN_REOPEN';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::TOGGLE, self::ANSWER, self::CLOSE, self::REOPEN])
            && ($subject instanceof Affectation || $subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface || !$user->isSuperAdmin() && $subject->getTerritory() !== $user->getTerritory()) {
            return false;
        }

        return match ($attribute) {
            self::TOGGLE => $this->canToggle($subject, $user),
            self::ANSWER => $this->canAnswer($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::REOPEN => $this->canReopen($subject, $user),
            default => false,
        };
    }

    private function canToggle(Signalement $signalement, UserInterface $user)
    {
        return ($user->isSuperAdmin() || $user->isTerritoryAdmin()) && Signalement::STATUS_ACTIVE === $signalement->getStatut();
    }

    private function canAnswer(Affectation $assignment, UserInterface $user): bool
    {
        return $assignment->getPartner() === $user->getPartner() && Signalement::STATUS_ACTIVE === $assignment->getSignalement()->getStatut();
    }

    private function canClose(Affectation $assignment, UserInterface $user): bool
    {
        return $this->canAnswer($assignment, $user) && Affectation::STATUS_ACCEPTED === $assignment->getStatut();
    }

    private function canReopen(Affectation $assignment, UserInterface $user): bool
    {
        return $this->canAnswer($assignment, $user) && Affectation::STATUS_CLOSED === $assignment->getStatut();
    }
}
