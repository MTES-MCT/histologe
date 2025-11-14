<?php

namespace App\Security\Voter;

use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @extends Voter<string, User>
 */
class UserVoter extends Voter
{
    public const string EDIT = 'USER_EDIT';
    public const string TRANSFER = 'USER_TRANSFER';
    public const string DELETE = 'USER_DELETE';
    public const string SEE_INJONCTION_BAILLEUR = 'SEE_INJONCTION_BAILLEUR';

    public function __construct(
        private readonly Security $security,
        private readonly RoleHierarchyInterface $roleHierarchy,
        #[Autowire(env: 'FEATURE_INJONCTION_BAILLEUR')]
        private readonly bool $featureInjonctionBailleur,
        #[Autowire(env: 'FEATURE_INJONCTION_BAILLEUR_DEPTS')]
        private readonly string $featureInjonctionBailleurDepts,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::TRANSFER, self::DELETE, self::SEE_INJONCTION_BAILLEUR])
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
            $vote?->addReason('L’utilisateur a été anonymisé.');

            return false;
        }
        if ($subject->isApiUser()) {
            $vote?->addReason('Action non autorisée sur un utilisateur API.');

            return false;
        }
        // TODO : retirer la 2è partie une fois le feature flipping FEATURE_INJONCTION_BAILLEUR activé et supprimé
        if ($this->security->isGranted('ROLE_ADMIN') && self::SEE_INJONCTION_BAILLEUR !== $attribute) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_ADMIN') && $subject->isSuperAdmin()) {
            $vote?->addReason('Action non autorisée sur un super administrateur.');

            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::TRANSFER => $this->canTransfer($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::SEE_INJONCTION_BAILLEUR => $this->canSeeInjonctionBailleur($user),
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

    private function canSeeInjonctionBailleur(User $user): bool
    {
        if (!$this->featureInjonctionBailleur) {
            return false;
        }
        $arrayDepts = json_decode($this->featureInjonctionBailleurDepts, true);

        return $user->isSuperAdmin()
            || ($user->isTerritoryAdmin() && in_array($user->getFirstTerritory()->getZip(), $arrayDepts))
            || count($user->getPartners()->filter(function (Partner $partner) use ($arrayDepts) {
                return $partner->hasCompetence(Qualification::AIDE_BAILLEURS) && in_array($partner->getTerritory()->getZip(), $arrayDepts);
            }));
    }
}
