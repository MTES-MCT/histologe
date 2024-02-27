<?php

namespace App\Security\Voter;

use App\Entity\Enum\Qualification;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const EDIT = 'USER_EDIT';
    public const TRANSFER = 'USER_TRANSFER';
    public const DELETE = 'USER_DELETE';
    public const CHECKMAIL = 'USER_CHECKMAIL';
    public const SEE_NDE = 'USER_SEE_NDE';

    public function __construct(
        private Security $security,
        private ParameterBagInterface $parameterBag
        ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CHECKMAIL, self::EDIT, self::TRANSFER, self::DELETE, self::SEE_NDE])
            && $subject instanceof User;
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
        if ($user->isSuperAdmin()) {
            return false;
        }

        return match ($attribute) {
            self::CHECKMAIL => $this->canCheckMail(),
            self::EDIT => $this->canEdit($subject, $user),
            self::TRANSFER => $this->canTransfer($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::SEE_NDE => $this->canSeeNde($user),
            default => false,
        };
    }

    private function canDelete(User $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    private function canEdit(User $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    private function canManage(User $subject, User $user): bool
    {
        if (!$user->getTerritory()) {
            return false;
        }
        if (!$user->getPartner()) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN_PARTNER') && $user->getTerritory() === $subject->getPartner()->getTerritory();
    }

    private function canTransfer(User $subject, User $user): bool
    {
        if (!$user->getTerritory()) {
            return false;
        }
        if (!$user->getPartner()) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->getTerritory() === $subject->getPartner()->getTerritory();
    }

    private function canCheckMail()
    {
        return $this->security->isGranted('ROLE_ADMIN_PARTNER');
    }

    public function canSeeNde(User $user): bool
    {
        $experimentationTerritories = $this->parameterBag->get('experimentation_territory');
        $isExperimentationTerritory = \array_key_exists(
            $user->getPartner()->getTerritory()->getZip(),
            $experimentationTerritories
        );
        if ($isExperimentationTerritory || $this->parameterBag->get('feature_new_form')) {
            return $user->isTerritoryAdmin()
            || \in_array(Qualification::NON_DECENCE_ENERGETIQUE, $user->getPartner()->getCompetence());
        }

        return false;
    }
}
