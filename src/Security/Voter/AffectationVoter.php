<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
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
    public const string AFFECTATION_REINIT = 'AFFECTATION_REINIT';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SEE, self::TOGGLE, self::ANSWER, self::CLOSE, self::REOPEN, self::AFFECTATION_REINIT], true)
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
            self::AFFECTATION_REINIT => $this->canReinit($subject, $user),
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
        $canAnswer = $affectation->getPartner() === $user->getPartnerInTerritory($affectation->getSignalement()->getTerritory())
            && SignalementStatus::ACTIVE === $affectation->getSignalement()->getStatut();

        if (!$this->isSynchronizeWithEsabora($affectation)) {
            return $canAnswer;
        }

        return false;
    }

    private function canClose(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && AffectationStatus::ACCEPTED === $affectation->getStatut();
    }

    private function canReopen(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && AffectationStatus::CLOSED === $affectation->getStatut();
    }

    private function canReinit(Affectation $affectation, User $user): bool
    {
        if (!in_array($affectation->getStatut(), [AffectationStatus::CLOSED, AffectationStatus::REFUSED])) {
            return false;
        }
        if (SignalementStatus::ACTIVE !== $affectation->getSignalement()->getStatut()) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->hasPartnerInTerritory($affectation->getSignalement()->getTerritory())) {
            return true;
        }

        return false;
    }

    private function isSynchronizeWithEsabora(Affectation $affectation): bool
    {
        if (PartnerType::ARS === $affectation->getPartner()->getType()
            || PartnerType::COMMUNE_SCHS === $affectation->getPartner()->getType()) {
            return $affectation->isSynchronized();
        }

        return false;
    }
}
