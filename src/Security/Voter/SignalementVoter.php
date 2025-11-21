<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Signalement>
 */
class SignalementVoter extends Voter
{
    public const string VALIDATE = 'SIGN_VALIDATE';
    public const string CLOSE = 'SIGN_CLOSE';
    public const string REOPEN = 'SIGN_REOPEN';
    public const string DELETE = 'SIGN_DELETE';
    public const string EDIT = 'SIGN_EDIT';
    public const string EDIT_CLOSED = 'SIGN_EDIT_CLOSED';
    public const string EDIT_INJONCTION = 'SIGN_EDIT_INJONCTION';
    public const string EDIT_DRAFT = 'SIGN_EDIT_DRAFT';
    public const string EDIT_NEED_VALIDATION = 'SIGN_EDIT_NEED_VALIDATION';
    public const string DELETE_DRAFT = 'SIGN_DELETE_DRAFT';
    public const string VIEW = 'SIGN_VIEW';
    public const string SUBSCRIBE = 'SIGN_SUBSCRIBE';
    public const string ADD_VISITE = 'SIGN_ADD_VISITE';
    public const string EDIT_NDE = 'SIGN_EDIT_NDE';
    public const string SEE_NDE = 'SIGN_SEE_NDE';
    public const string CREATE_SUIVI = 'CREATE_SUIVI';
    public const string AFFECTATION_TOGGLE = 'AFFECTATION_TOGGLE';
    public const string AFFECTATION_SEE = 'AFFECTATION_SEE';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute,
            [
                self::EDIT,
                self::EDIT_CLOSED,
                self::EDIT_INJONCTION,
                self::EDIT_DRAFT,
                self::EDIT_NEED_VALIDATION,
                self::VIEW,
                self::SUBSCRIBE,
                self::DELETE,
                self::VALIDATE,
                self::CLOSE,
                self::REOPEN,
                self::ADD_VISITE,
                self::EDIT_NDE,
                self::SEE_NDE,
                self::DELETE_DRAFT,
                self::CREATE_SUIVI,
                self::AFFECTATION_TOGGLE,
                self::AFFECTATION_SEE,
            ])
            && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié.');

            return false;
        }

        if (in_array($attribute, [self::ADD_VISITE])) {
            return $this->canAddVisite($subject, $user);
        }

        if (in_array($attribute, [self::EDIT_NDE, self::SEE_NDE])) {
            if (self::EDIT_NDE === $attribute) {
                return $this->canEditNDE($subject, $user);
            }

            return $this->canSeeNde($subject, $user);
        }

        return match ($attribute) {
            self::VALIDATE => $this->canValidate($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::REOPEN => $this->canReopen($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::EDIT_CLOSED => $this->canEditClosed($subject, $user),
            self::EDIT_INJONCTION => $this->canEditInjonction($subject, $user),
            self::EDIT_NEED_VALIDATION => $this->canEditNeedValidation($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::SUBSCRIBE => $this->canSubscribe($subject, $user),
            self::EDIT_DRAFT, self::DELETE_DRAFT => $this->canEditDraft($subject, $user),
            self::CREATE_SUIVI => $this->canCreateSuivi($subject, $user, $vote),
            self::AFFECTATION_TOGGLE => $this->canToggleAffectation($subject, $user),
            self::AFFECTATION_SEE => $this->canSeeAffectation($subject, $user),
            default => false,
        };
    }

    private function isAdminOrTerritoryAdmin(Signalement $signalement, User $user): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
                || ($user->isTerritoryAdmin() && $user->hasPartnerInTerritory($signalement->getTerritory()));
    }

    private function canValidate(Signalement $signalement, User $user): bool
    {
        if (SignalementStatus::NEED_VALIDATION !== $signalement->getStatut()) {
            return false;
        }

        return $this->isAdminOrTerritoryAdmin($signalement, $user);
    }

    private function canClose(Signalement $signalement, User $user): bool
    {
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }

        return $this->isAdminOrTerritoryAdmin($signalement, $user);
    }

    private function canReopen(Signalement $signalement, User $user): bool
    {
        return $this->canDelete($signalement, $user);
    }

    private function canDelete(Signalement $signalement, User $user): bool
    {
        if (!\in_array($signalement->getStatut(), [SignalementStatus::CLOSED, SignalementStatus::REFUSED])) {
            return false;
        }

        return $this->isAdminOrTerritoryAdmin($signalement, $user);
    }

    private function canEditStatus(Signalement $signalement, User $user, SignalementStatus $status, ?bool $checkAffectationAccepted = true): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($status !== $signalement->getStatut()) {
            return false;
        }
        if ($user->isTerritoryAdmin() && $user->hasPartnerInTerritory($signalement->getTerritory())) {
            return true;
        }
        if (!$checkAffectationAccepted) {
            return false;
        }
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner?->getId() && AffectationStatus::ACCEPTED === $affectation->getStatut();
        })->count() > 0;
    }

    private function canEdit(Signalement $signalement, User $user): bool
    {
        return $this->canEditStatus($signalement, $user, SignalementStatus::ACTIVE);
    }

    private function canEditInjonction(Signalement $signalement, User $user): bool
    {
        if ($this->canEditStatus($signalement, $user, SignalementStatus::ACTIVE)) {
            return true;
        }

        return $this->canEditStatus($signalement, $user, SignalementStatus::INJONCTION_BAILLEUR);
    }

    private function canEditClosed(Signalement $signalement, User $user): bool
    {
        if ($this->canEditStatus($signalement, $user, SignalementStatus::ACTIVE)) {
            return true;
        }

        return $this->canEditStatus($signalement, $user, SignalementStatus::CLOSED, false);
    }

    private function canEditNeedValidation(Signalement $signalement, User $user): bool
    {
        if ($this->canEditStatus($signalement, $user, SignalementStatus::ACTIVE)) {
            return true;
        }

        return $this->canEditStatus($signalement, $user, SignalementStatus::NEED_VALIDATION, false);
    }

    private function canEditDraft(Signalement $signalement, User $user): bool
    {
        if (SignalementStatus::DRAFT !== $signalement->getStatut()) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($user->getId() === $signalement->getCreatedBy()->getId()) {
            return true;
        }

        return false;
    }

    private function canView(Signalement $signalement, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN') && !in_array($signalement->getStatut(), [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])) {
            return true;
        }

        if (in_array($signalement->getStatut(), SignalementStatus::excludedStatuses(includeInjonctionBailleur: false))) {
            return false;
        }

        if (!$user->hasPartnerInTerritory($signalement->getTerritory())) {
            return false;
        }

        if ($user->isTerritoryAdmin()) {
            return true;
        }

        $partner = $user->getPartnerInTerritory($signalement->getTerritory());
        if (!$partner) {
            return false;
        }

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner->getId();
        })->count() > 0;
    }

    private function canSubscribe(Signalement $signalement, User $user): bool
    {
        // en attendant les précisions sur les afectations d'injonction bailleur
        if (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            return false;
        }
        if (SignalementStatus::CLOSED === $signalement->getStatut()) {
            return false;
        }

        return $this->canView($signalement, $user);
    }

    public function canAddVisite(Signalement $signalement, User $user): bool
    {
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTerritoryAdmin() && $user->hasPartnerInTerritory($signalement->getTerritory())) {
            return true;
        }
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner->getId()
                && \in_array(Qualification::VISITES, $partner->getCompetence())
                && AffectationStatus::ACCEPTED == $affectation->getStatut();
        })->count() > 0;
    }

    private function canEditNDE(Signalement $signalement, User $user): bool
    {
        $signalementQualificationNDE = $signalement->getSignalementQualifications()->filter(function ($qualification) {
            return Qualification::NON_DECENCE_ENERGETIQUE === $qualification->getQualification();
        })->first();

        $isSignalementNDEActif = false;
        if (null !== $signalementQualificationNDE && false !== $signalementQualificationNDE) {
            $isSignalementNDEActif = QualificationStatus::ARCHIVED != $signalementQualificationNDE->getStatus();
        }

        return $isSignalementNDEActif && $this->canSeeNde($signalement, $user)
        && $this->canEdit($signalement, $user);
    }

    private function canSeeNde(Signalement $signalement, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            return true;
        }
        foreach ($user->getPartners() as $partner) {
            if ($signalement->getTerritory() !== $partner->getTerritory()) {
                continue;
            }
            if (\in_array(Qualification::NON_DECENCE_ENERGETIQUE, $partner->getCompetence())) {
                return true;
            }
        }

        return false;
    }

    private function canCreateSuivi(Signalement $signalement, User $user, ?Vote $vote = null): bool
    {
        if (!$user->isSuperAdmin() && !$user->hasPartnerInTerritory($signalement->getTerritory())) {
            $vote?->addReason('L\'utilisateur n\'a pas les droits suffisants dans le territoire demandé.');

            return false;
        }
        if (!in_array($signalement->getStatut(), [SignalementStatus::ACTIVE, SignalementStatus::INJONCTION_BAILLEUR])) {
            return false;
        }
        if ($user->isTerritoryAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner->getId() && AffectationStatus::ACCEPTED == $affectation->getStatut();
        })->count() > 0;
    }

    private function canSeeAffectation(Signalement $signalement, User $user): bool
    {
        if (SignalementStatus::NEED_VALIDATION === $signalement->getStatut()) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if (!$user->hasPartnerInTerritory($signalement->getTerritory())) {
            return false;
        }
        if ($user->isTerritoryAdmin() || $user->hasPermissionAffectation()) {
            return true;
        }

        return false;
    }

    private function canToggleAffectation(Signalement $signalement, User $user): bool
    {
        if (!in_array($signalement->getStatut(), [SignalementStatus::INJONCTION_BAILLEUR, SignalementStatus::ACTIVE])) {
            return false;
        }

        return $this->canSeeAffectation($signalement, $user);
    }
}
