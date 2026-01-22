<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Affectation>
 */
class AffectationVoter extends Voter
{
    public const string AFFECTATION_ACCEPT_OR_REFUSE = 'AFFECTATION_ACCEPT_OR_REFUSE';
    public const string AFFECTATION_CANCEL_REFUSED = 'AFFECTATION_CANCEL_REFUSED';
    public const string AFFECTATION_CLOSE = 'AFFECTATION_CLOSE';
    public const string AFFECTATION_REOPEN = 'AFFECTATION_REOPEN';
    public const string AFFECTATION_REINIT = 'AFFECTATION_REINIT';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::AFFECTATION_ACCEPT_OR_REFUSE, self::AFFECTATION_CANCEL_REFUSED, self::AFFECTATION_CLOSE, self::AFFECTATION_REOPEN, self::AFFECTATION_REINIT], true) && ($subject instanceof Affectation);
    }

    /**
     * @param Affectation $subject
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
            self::AFFECTATION_ACCEPT_OR_REFUSE => $this->canAcceptOrRefuse($subject, $user),
            self::AFFECTATION_CANCEL_REFUSED => $this->canCancelRefused($subject, $user),
            self::AFFECTATION_CLOSE => $this->canClose($subject, $user),
            self::AFFECTATION_REOPEN => $this->canReopen($subject, $user),
            self::AFFECTATION_REINIT => $this->canReinit($subject, $user),
            default => false,
        };
    }

    private function canAnswer(Affectation $affectation, User $user): bool
    {
        $canAnswer = $affectation->getPartner() === $user->getPartnerInTerritory($affectation->getSignalement()->getTerritory())
            && in_array($affectation->getSignalement()->getStatut(), [SignalementStatus::INJONCTION_BAILLEUR, SignalementStatus::ACTIVE]);

        if (!$affectation->isSynchronizeWithEsabora()) {
            return $canAnswer;
        }

        return false;
    }

    private function canAcceptOrRefuse(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user)
        && (AffectationStatus::WAIT === $affectation->getStatut() || AffectationStatus::REFUSED === $affectation->getStatut())
        ;
    }

    private function canCancelRefused(Affectation $affectation, User $user): bool
    {
        return $this->canAnswer($affectation, $user) && AffectationStatus::REFUSED === $affectation->getStatut();
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
        if (!in_array($affectation->getSignalement()->getStatut(), [SignalementStatus::INJONCTION_BAILLEUR, SignalementStatus::ACTIVE])) {
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
}
