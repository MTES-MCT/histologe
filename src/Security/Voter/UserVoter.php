<?php

namespace App\Security\Voter;

use App\Entity\Behaviour\BoUserInterface;
use App\Entity\Enum\Qualification;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserVoter extends Voter
{
    public const string EDIT = 'USER_EDIT';
    public const string TRANSFER = 'USER_TRANSFER';
    public const string DELETE = 'USER_DELETE';
    public const string SEE_NDE = 'USER_SEE_NDE';

    public function __construct(
        private readonly Security $security,
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::TRANSFER, self::DELETE, self::SEE_NDE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof BoUserInterface) {
            return false;
        }
        if ($subject->getAnonymizedAt()) {
            return false;
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($subject->isSuperAdmin() || $subject->isApiUser()) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::TRANSFER => $this->canTransfer($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::SEE_NDE => $this->canSeeNde($user),
            default => false,
        };
    }

    private function canDelete(User $subject, User $user): bool
    {
        return $this->canFullManage($subject, $user);
    }

    private function canEdit(User $subject, User $user): bool
    {
        $subjectRoles = $this->roleHierarchy->getReachableRoleNames($subject->getRoles());
        $userRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        // subject has more roles than me, i can't edit it
        if (\count($subjectRoles) > \count($userRoles)) {
            return false;
        }
        if (!$user->getFirstTerritory()) {
            return false;
        }
        $commonTerritoriesKeys = array_intersect_key($subject->getPartnersTerritories(), $user->getPartnersTerritories());

        return $this->security->isGranted('ROLE_ADMIN_PARTNER') && \count($commonTerritoriesKeys);
    }

    private function canFullManage(User $subject, User $user): bool
    {
        if (!$this->canEdit($subject, $user)) {
            return false;
        }
        $commonTerritoriesKeys = array_intersect_key($subject->getPartnersTerritories(), $user->getPartnersTerritories());

        return $this->security->isGranted('ROLE_ADMIN_TERRITORY') && \count($commonTerritoriesKeys);
    }

    private function canTransfer(User $subject, User $user): bool
    {
        return $this->canFullManage($subject, $user);
    }

    public function canSeeNde(User $user): bool
    {
        if ($user->isTerritoryAdmin()) {
            return true;
        }
        foreach ($user->getPartners() as $partner) {
            if (\in_array(Qualification::NON_DECENCE_ENERGETIQUE, $partner->getCompetence())) {
                return true;
            }
        }

        return false;
    }
}
