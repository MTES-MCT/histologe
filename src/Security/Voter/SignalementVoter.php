<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use App\Entity\User;
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
    public const REOPEN = 'SIGN_REOPEN';
    public const ADD_VISITE = 'SIGN_ADD_VISITE';
    public const USAGER_EDIT = 'SIGN_USAGER_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::VALIDATE, self::REOPEN, self::CLOSE, self::ADD_VISITE, self::USAGER_EDIT])
            && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            if (self::USAGER_EDIT === $attribute && !\in_array($subject->getStatut(), Signalement::DISABLED_STATUSES)) {
                return true;
            }

            return false;
        }

        if (self::ADD_VISITE == $attribute) {
            return $this->canAddVisite($subject, $user);
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::VALIDATE => $this->canValidate($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::REOPEN => $this->canReopen($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            default => false,
        };
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
        return $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canReopen(Signalement $signalement, User $user): bool
    {
        return Signalement::STATUS_CLOSED === $signalement->getStatut() && $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canEdit(Signalement $signalement, User $user): bool
    {
        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId();
        })->count() > 0 || $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canView(Signalement $signalement, User $user): bool
    {
        if ($this->canEdit($signalement, $user)) {
            return true;
        }

        return false;
    }

    public function canAddVisite(Signalement $signalement, User $user): bool
    {
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut() && Signalement::STATUS_NEED_PARTNER_RESPONSE !== $signalement->getStatut()) {
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
}
