<?php

namespace App\Security\Voter;

use App\Entity\Partner;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PartnerVoter extends Voter
{
    public const CREATE = 'PARTNER_CREATE';
    public const EDIT = 'PARTNER_EDIT';
    public const DELETE = 'PARTNER_DELETE';
    public const USER_CREATE = 'USER_CREATE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::EDIT, self::DELETE, self::USER_CREATE]) && ($subject instanceof Partner);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::USER_CREATE => $this->canCreateUser($subject, $user),
            default => false,
        };
    }

    private function canEdit(Partner $partner, User $user): bool
    {
        return $this->canManage($partner, $user);
    }

    private function canDelete(Partner $partner, User $user): bool
    {
        if (!$this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            return false;
        }

        return $this->canManage($partner, $user);
    }

    private function canCreateUser(Partner $partner, User $user): bool
    {
        return $this->canManage($partner, $user);
    }

    private function canManage(Partner $partner, User $user): bool
    {
        if (!$user->getTerritory()) {
            return false;
        }
        if (!$user->getPartner()) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN_PARTNER') && $user->getPartner() === $partner) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->getTerritory() === $partner->getTerritory()) {
            return true;
        }

        return false;
    }
}
