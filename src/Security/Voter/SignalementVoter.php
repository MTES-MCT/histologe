<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Security\User\SignalementUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SignalementVoter extends Voter
{
    public const string VALIDATE = 'SIGN_VALIDATE';
    public const string CLOSE = 'SIGN_CLOSE';
    public const string REOPEN = 'SIGN_REOPEN';
    public const string DELETE = 'SIGN_DELETE';
    public const string EDIT = 'SIGN_EDIT';
    public const string EDIT_DRAFT = 'SIGN_EDIT_DRAFT';
    public const string DELETE_DRAFT = 'SIGN_DELETE_DRAFT';
    public const string VIEW = 'SIGN_VIEW';
    public const string ADD_VISITE = 'SIGN_ADD_VISITE';
    public const string ADD_ARRETE = 'SIGN_ADD_ARRETE';
    public const string USAGER_EDIT = 'SIGN_USAGER_EDIT';
    public const string USAGER_EDIT_PROCEDURE = 'SIGN_USAGER_EDIT_PROCEDURE';
    public const string EDIT_NDE = 'SIGN_EDIT_NDE';
    public const string SEE_NDE = 'SIGN_SEE_NDE';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute,
            [
                self::EDIT,
                self::EDIT_DRAFT,
                self::VIEW,
                self::DELETE,
                self::VALIDATE,
                self::CLOSE,
                self::REOPEN,
                self::ADD_VISITE,
                self::ADD_ARRETE,
                self::USAGER_EDIT,
                self::USAGER_EDIT_PROCEDURE,
                self::EDIT_NDE,
                self::SEE_NDE,
                self::DELETE_DRAFT,
            ])
            && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user || $user instanceof SignalementUser) {
            if (self::USAGER_EDIT === $attribute && $this->canUsagerEdit($subject)) {
                return true;
            }
            if (self::USAGER_EDIT_PROCEDURE === $attribute && $this->canUsagerEditProcedure($subject)) {
                return true;
            }

            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        if (in_array($attribute, [self::ADD_ARRETE, self::ADD_VISITE])) {
            return $this->canAddVisite($subject, $user);
        }

        if (in_array($attribute, [self::EDIT_NDE, self::SEE_NDE])) {
            return match ($attribute) {
                self::EDIT_NDE => $this->canEditNDE($subject, $user),
                self::SEE_NDE => $this->canSeeNde($subject, $user),
                default => false,
            };
        }

        return match ($attribute) {
            self::VALIDATE => $this->canValidate($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::REOPEN => $this->canReopen($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::USAGER_EDIT => $this->canUsagerEdit($subject),
            self::USAGER_EDIT_PROCEDURE => $this->canUsagerEditProcedure($subject),
            self::EDIT_DRAFT, self::DELETE_DRAFT => $this->canEditDraft($subject, $user),
            default => false,
        };
    }

    private function canUsagerEdit(Signalement $signalement): bool
    {
        if (SignalementStatus::ARCHIVED !== $signalement->getStatut()
            && SignalementStatus::REFUSED !== $signalement->getStatut()
            && SignalementStatus::DRAFT !== $signalement->getStatut()
            && SignalementStatus::DRAFT_ARCHIVED !== $signalement->getStatut()
        ) {
            if (SignalementStatus::CLOSED === $signalement->getStatut()) {
                if (!$signalement->getClosedAt()) {
                    return false;
                }
                $datePostCloture = $signalement->getClosedAt()->modify('+ 30days');
                $today = new \DateTimeImmutable();
                if ($today < $datePostCloture && !$signalement->hasSuiviUsagerPostCloture()) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    private function canUsagerEditProcedure(Signalement $signalement): bool
    {
        if (SignalementStatus::ACTIVE === $signalement->getStatut()
            || SignalementStatus::NEED_VALIDATION === $signalement->getStatut()
        ) {
            return true;
        }

        return false;
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

    private function canEdit(Signalement $signalement, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if (SignalementStatus::CLOSED === $signalement->getStatut() && $user->isTerritoryAdmin() && $user->hasPartnerInTerritory($signalement->getTerritory())) {
            return true;
        }
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        if ($user->isTerritoryAdmin() && $user->hasPartnerInTerritory($signalement->getTerritory())) {
            return true;
        }
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner?->getId() && Affectation::STATUS_ACCEPTED === $affectation->getStatut();
        })->count() > 0;
    }

    private function canView(Signalement $signalement, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN') && SignalementStatus::DRAFT !== $signalement->getStatut() && SignalementStatus::DRAFT_ARCHIVED !== $signalement->getStatut()) {
            return true;
        }

        if (SignalementStatus::ARCHIVED === $signalement->getStatut()
        || SignalementStatus::DRAFT === $signalement->getStatut()
        || SignalementStatus::DRAFT_ARCHIVED === $signalement->getStatut()) {
            return false;
        }

        if (!$user->hasPartnerInTerritory($signalement->getTerritory())) {
            return false;
        }

        $partner = $user->getPartnerInTerritory($signalement->getTerritory());
        if ($user->isTerritoryAdmin()) {
            return true;
        }

        if (!$partner) {
            return false;
        }

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner->getId();
        })->count() > 0;
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
                && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
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
}
