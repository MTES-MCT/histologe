<?php

namespace App\Security\Voter;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @extends Voter<string, User>
 */
class UserVoter extends Voter
{
    public const string USER_EDIT = 'USER_EDIT';
    public const string USER_TRANSFER = 'USER_TRANSFER';
    public const string USER_DELETE = 'USER_DELETE';
    public const string USER_DISABLE = 'USER_DISABLE';

    public function __construct(
        private readonly Security $security,
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::USER_EDIT, self::USER_TRANSFER, self::USER_DELETE, self::USER_DISABLE])
            && $subject instanceof User;
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié');

            return false;
        }
        if ($subject->getAnonymizedAt()) {
            $vote?->addReason('L\'utilisateur a été anonymisé.');

            return false;
        }
        if ($subject->isApiUser()) {
            $vote?->addReason('Action non autorisée sur un utilisateur API.');

            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN') && self::USER_DISABLE !== $attribute) {
            return true;
        } elseif ($subject->isSuperAdmin()) {
            $vote?->addReason('Action non autorisée sur un super administrateur.');

            return false;
        }

        return match ($attribute) {
            self::USER_EDIT => $this->canEdit($subject, $user),
            self::USER_TRANSFER => $this->canTransfer($subject, $user),
            self::USER_DELETE => $this->canDelete($subject, $user),
            self::USER_DISABLE => $this->canDisable($subject),
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

    private function canDisable(User $subject): bool
    {
        return $this->security->isGranted('ROLE_ADMIN') && UserStatus::ACTIVE === $subject->getStatut();
    }
}
