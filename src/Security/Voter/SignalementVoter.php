<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementVoter extends Voter
{
    public const VALIDATE = 'SIGN_VALIDATE';
    public const CLOSE = 'SIGN_CLOSE';
    public const DELETE = 'SIGN_DELETE';
    public const EDIT = 'SIGN_EDIT';
    public const VIEW = 'SIGN_VIEW';
    public const ADD_VISITE = 'SIGN_ADD_VISITE';
    public const USAGER_EDIT = 'SIGN_USAGER_EDIT';
    public const EDIT_NDE = 'SIGN_EDIT_NDE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::VALIDATE, self::CLOSE, self::ADD_VISITE, self::USAGER_EDIT, self::EDIT_NDE])
            && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            if (self::USAGER_EDIT === $attribute && $this->canUsagerEdit($subject)) {
                return true;
            }

            return false;
        }

        if (self::ADD_VISITE == $attribute) {
            return $this->canAddVisite($subject, $user);
        }

        if (self::EDIT_NDE == $attribute) {
            return $this->canEditNDE($subject, $user);
        }

        if ($this->security->isGranted('ROLE_ADMIN') && self::DELETE !== $attribute) {
            return true;
        }

        return match ($attribute) {
            self::VALIDATE => $this->canValidate($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            default => false,
        };
    }

    private function canUsagerEdit(Signalement $signalement): bool
    {
        if (Signalement::STATUS_ARCHIVED !== $signalement->getStatut()
            && Signalement::STATUS_REFUSED !== $signalement->getStatut()
        ) {
            if (Signalement::STATUS_CLOSED === $signalement->getStatut()) {
                $datePostCloture = $signalement->getClosedAt()->modify('+ 30days');
                $today = new \DateTimeImmutable();
                if ($today < $datePostCloture && !$signalement->hasSuiviUsagePostCloture()) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    private function canValidate(Signalement $signalement, User $user): bool
    {
        return Signalement::STATUS_NEED_VALIDATION === $signalement->getStatut() && $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canClose(Signalement $signalement, User $user): bool
    {
        return $signalement->getStatut() >= Signalement::STATUS_ACTIVE && $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canDelete(Signalement $signalement, User $user): bool
    {
        if (!\in_array($signalement->getStatut(), [Signalement::STATUS_CLOSED, Signalement::STATUS_REFUSED])) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->getTerritory()->getId() === $signalement->getTerritory()->getId()) {
            return true;
        }

        return false;
    }

    private function canEdit(Signalement $signalement, User $user): bool
    {
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut()) {
            return false;
        }

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId() && Affectation::STATUS_ACCEPTED === $affectation->getStatut();
        })->count() > 0 || ($user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory())
        || $user->isSuperAdmin();
    }

    private function canView(Signalement $signalement, User $user): bool
    {
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            return false;
        }

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId();
        })->count() > 0 || $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    public function canAddVisite(Signalement $signalement, User $user): bool
    {
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut()) {
            return false;
        }

        $isUserInAffectedPartnerWithQualificationVisite = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId()
                && \in_array(Qualification::VISITES, $user->getPartner()->getCompetence())
                && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
        })->count() > 0;
        $isUserTerritoryAdminOfSignalementTerritory = $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();

        return $user->isSuperAdmin() || $isUserInAffectedPartnerWithQualificationVisite || $isUserTerritoryAdminOfSignalementTerritory;
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

        return $isSignalementNDEActif && $this->security->isGranted(UserVoter::SEE_NDE, $user)
        && $this->canEdit($signalement, $user);
    }
}
