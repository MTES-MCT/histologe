<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use Doctrine\Common\Collections\ArrayCollection;
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
    public const EXPORT = 'SIGN_EXPORT';
    public const ADD_VISITE = 'SIGN_ADD_VISITE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::VALIDATE, self::REOPEN, self::CLOSE, self::EXPORT, self::ADD_VISITE])
            && ($subject instanceof Signalement || $subject instanceof ArrayCollection);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
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
            self::EXPORT => $this->canExport($subject, $user),
            self::ADD_VISITE => $this->canAddVisite($subject, $user),
            default => false,
        };
    }

    private function canValidate(Signalement $signalement, UserInterface $user): bool
    {
        return Signalement::STATUS_NEED_VALIDATION === $signalement->getStatut() && $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canClose(Signalement $signalement, UserInterface $user): bool
    {
        return $signalement->getStatut() >= Signalement::STATUS_ACTIVE && $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canDelete(Signalement $signalement, UserInterface $user): bool
    {
        return $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canReopen(Signalement $signalement, UserInterface $user): bool
    {
        return Signalement::STATUS_CLOSED === $signalement->getStatut() && $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canEdit(Signalement $signalement, UserInterface $user): bool
    {
        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId();
        })->count() > 0 || $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }

    private function canView(Signalement $signalement, UserInterface $user): bool
    {
        if ($this->canEdit($signalement, $user)) {
            return true;
        }

        return false;
    }

    public function canExport(ArrayCollection $signalements, UserInterface $user): bool
    {
        return $user->isTerritoryAdmin() && 0 == $signalements->filter(function (Signalement $signalement) use ($user) {
            return $signalement->getTerritory() !== $user->getTerritory();
        })->count();
    }

    public function canAddVisite(Signalement $signalement, UserInterface $user): bool
    {
        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId() && \in_array(Qualification::VISITES, $user->getPartner()->getCompetence());
        })->count() > 0 || $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();
    }
}
