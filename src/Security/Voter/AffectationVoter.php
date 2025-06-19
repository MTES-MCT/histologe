<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AffectationVoter extends Voter
{
    public const string SEE = 'AFFECTATION_SEE';
    public const string TOGGLE = 'AFFECTATION_TOGGLE';
    public const string ANSWER = 'AFFECTATION_ANSWER';
    public const string CLOSE = 'AFFECTATION_CLOSE';
    public const string REOPEN = 'AFFECTATION_REOPEN';
    public const string UPDATE_STATUT = 'AFFECTATION_UPDATE_STATUS';

    private const array VALID_WORKFLOW_STATUT = [
        Affectation::STATUS_WAIT => [Affectation::STATUS_ACCEPTED, Affectation::STATUS_REFUSED],
        Affectation::STATUS_ACCEPTED => [Affectation::STATUS_CLOSED],
        Affectation::STATUS_REFUSED => [Affectation::STATUS_ACCEPTED],
        Affectation::STATUS_CLOSED => [Affectation::STATUS_WAIT],
    ];

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SEE, self::TOGGLE, self::ANSWER, self::CLOSE, self::REOPEN, self::UPDATE_STATUT])
            && ($subject instanceof Affectation || $subject instanceof Signalement);
    }

    /**
     * @param Signalement|Affectation $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isSuperAdmin() && !$user->hasPartnerInTerritory($subject->getTerritory())) {
            $vote?->addReason('L\'utilisateur n\'a pas les droits suffisants dans le territoire demandé.');

            return false;
        }

        return match ($attribute) {
            self::SEE => $this->canSee($subject, $user),
            self::TOGGLE => $this->canToggle($subject, $user),
            self::ANSWER => $this->canAnswer($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::REOPEN => $this->canReopen($subject, $user),
            self::UPDATE_STATUT => $this->canUpdateStatut($subject, $user),
            default => false,
        };
    }

    private function canSee(Signalement $signalement, User $user): bool
    {
        return (
            $user->isSuperAdmin()
            || $user->isTerritoryAdmin()
            || $user->hasPermissionAffectation()
        )
            && SignalementStatus::NEED_VALIDATION !== $signalement->getStatut();
    }

    private function canToggle(Signalement $signalement, User $user): bool
    {
        return (
            $user->isSuperAdmin()
            || $user->isTerritoryAdmin()
            || $user->hasPermissionAffectation()
        )
            && SignalementStatus::ACTIVE === $signalement->getStatut();
    }

    private function canAnswer(Affectation $affectation, User $user): bool
    {
        return $affectation->getPartner() === $user->getPartnerInTerritory($affectation->getSignalement()->getTerritory()) && SignalementStatus::ACTIVE === $affectation->getSignalement()->getStatut();
    }

    private function canClose(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && Affectation::STATUS_ACCEPTED === $affectation->getStatut();
    }

    private function canReopen(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && Affectation::STATUS_CLOSED === $affectation->getStatut();
    }

    private function canUpdateStatut(Affectation $affectation, User $user): bool
    {
        $newStatut = $affectation->getNextStatut();
        $previousStatut = $affectation->getStatut();
        $canUpdateStatut = isset(self::VALID_WORKFLOW_STATUT[$previousStatut])
            && in_array($newStatut, self::VALID_WORKFLOW_STATUT[$previousStatut], true);

        return $this->canAnswer($affectation, $user) && $canUpdateStatut;
    }
}
