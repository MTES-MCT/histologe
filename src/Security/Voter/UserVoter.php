<?php

namespace App\Security\Voter;

use App\Entity\Enum\Qualification;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CHECKMAIL, self::CREATE, self::REACTIVE, self::EDIT, self::TRANSFER, self::DELETE, self::SEE_NDE])
            && $subject instanceof User;
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

    private function canCreate(User $subject, User $user): bool
    {
        if ($this->canDelete($subject, $user)) {
            return true;
        }

        return false;
    }

    private function canDelete(User $subject, User $user): bool
    {
        return ($user->isTerritoryAdmin() || $user->isPartnerAdmin()) && $user->getTerritory() === $subject->getPartner()->getTerritory();
    }

    private function canEdit(User $subject, User $user): bool
    {
        if ($this->canDelete($subject, $user)) {
            return true;
        }

        return $subject->getId() === $user->getId();
    }

    private function canTransfer(User $subject, User $user): bool
    {
        return $user->isTerritoryAdmin() && $user->getTerritory() === $subject->getTerritory();
    }

    private function canCheckMail(mixed $subject, User $user)
    {
        return $user->isTerritoryAdmin() || $user->isPartnerAdmin();
    }

    private function canReactive(User $user)
    {
        return $user->isSuperAdmin();
    }

    public function canSeeNde(User $user): bool
    {
        // $experimentationTerritories = $this->parameterBag->get('experimentation_territory');
        // $isExperimentationTerritory = \array_key_exists($user->getPartner()->getTerritory()->getZip(), $experimentationTerritories);
        // if ($isExperimentationTerritory) {
        return $user->isTerritoryAdmin() || \in_array(Qualification::NON_DECENCE_ENERGETIQUE, $user->getPartner()->getCompetence());
        // }

        // return false;
    }
}
