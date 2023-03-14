<?php

namespace App\Security\Voter;

use App\Entity\Enum\Qualification;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const CREATE = 'USER_CREATE';
    public const EDIT = 'USER_EDIT';
    public const REACTIVE = 'USER_REACTIVE';
    public const TRANSFER = 'USER_TRANSFER';
    public const DELETE = 'USER_DELETE';
    public const CHECKMAIL = 'USER_CHECKMAIL';
    public const SEE_NDE = 'USER_SEE_NDE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CHECKMAIL, self::CREATE, self::REACTIVE, self::EDIT, self::TRANSFER, self::DELETE, self::SEE_NDE])
            && $subject instanceof User;
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
            self::CHECKMAIL => $this->canCheckMail($subject, $user),
            self::CREATE => $this->canCreate($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::TRANSFER => $this->canTransfer($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::REACTIVE => $this->canReactive($user),
            self::SEE_NDE => $this->canSeeNde($user),
            default => false,
        };
    }

    private function canCreate(User $subject, UserInterface $user): bool
    {
        if ($this->canDelete($subject, $user)) {
            return true;
        }

        return false;
    }

    private function canDelete(User $subject, UserInterface $user): bool
    {
        return ($user->isTerritoryAdmin() || $user->isPartnerAdmin()) && $user->getTerritory() === $subject->getPartner()->getTerritory();
    }

    private function canEdit(User $subject, UserInterface $user): bool
    {
        if ($this->canDelete($subject, $user)) {
            return true;
        }

        return $subject->getId() === $user->getId();
    }

    private function canTransfer(User $subject, UserInterface $user): bool
    {
        return $user->isTerritoryAdmin() && $user->getTerritory() === $subject->getTerritory();
    }

    private function canCheckMail(mixed $subject, UserInterface $user)
    {
        return $user->isTerritoryAdmin() || $user->isPartnerAdmin();
    }

    private function canReactive(UserInterface $user)
    {
        return $user->isSuperAdmin();
    }

    public function canSeeNde(UserInterface $user): bool
    {
        return $user->isTerritoryAdmin() || \in_array(Qualification::NON_DECENCE_ENERGETIQUE, $user->getPartner()->getCompetence());
    }
}
