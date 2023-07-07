<?php

namespace App\Security\Voter;

use App\Entity\Partner;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PartnerVoter extends Voter
{
    public const LIST = 'PARTNER_LIST';
    public const CREATE = 'PARTNER_CREATE';
    public const VIEW = 'PARTNER_VIEW';
    public const EDIT = 'PARTNER_EDIT';
    public const DELETE = 'PARTNER_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::LIST, self::CREATE, self::EDIT, self::VIEW, self::DELETE])
            && ($subject instanceof Partner || !$subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::CREATE => $this->canCreate($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::LIST => $this->canList($user),
            default => false,
        };
    }

    private function canEdit(Partner $partner, User $user): bool
    {
        if ($this->canList($user)) {
            return true;
        }

        return $user->getPartner()->getId() === $partner->getId() && $user->isPartnerAdmin();
    }

    private function canList(User $user): bool
    {
        return $user->isTerritoryAdmin();
    }

    private function canView(Partner $partner, User $user): bool
    {
        if ($this->canList($user)) {
            return true;
        }

        return $user->getPartner()->getId() === $partner->getId();
    }

    private function canDelete(Partner $partner, User $user): bool
    {
        if ($this->canList($user)) {
            return true;
        }

        return $user->getPartner()->getId() === $partner->getId() && $user->isPartnerAdmin();
    }

    private function canCreate(mixed $subject, User $user)
    {
        return $this->canList($user);
    }
}
