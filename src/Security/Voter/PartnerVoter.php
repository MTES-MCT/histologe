<?php

namespace App\Security\Voter;

use App\Entity\Partner;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PartnerVoter extends Voter
{
    public const CREATE = 'PARTNER_CREATE';
    public const EDIT = 'PARTNER_EDIT';
    public const DELETE = 'PARTNER_DELETE';
    public const USER_CREATE = 'USER_CREATE';
    public const ASSIGN_PERMISSION_AFFECTATION = 'ASSIGN_PERMISSION_AFFECTATION';

    public function __construct(
        private Security $security,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::EDIT, self::DELETE, self::USER_CREATE, self::ASSIGN_PERMISSION_AFFECTATION]) && ($subject instanceof Partner);
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
            self::ASSIGN_PERMISSION_AFFECTATION => $this->canAssignPermissionAffectation($subject, $user),
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

    private function canAssignPermissionAffectation(Partner $partner, User $user): bool
    {
        if (!$this->parameterBag->get('feature_permission_affectation') || !$user->getFirstTerritory()) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->hasPartnerInTerritory($partner->getTerritory())) {
            return true;
        }

        return false;
    }

    private function canManage(Partner $partner, User $user): bool
    {
        if (!$user->getFirstTerritory()) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN_PARTNER') && $user->hasPartner($partner)) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->hasPartnerInTerritory($partner->getTerritory())) {
            return true;
        }

        return false;
    }
}
