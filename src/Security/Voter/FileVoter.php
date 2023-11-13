<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class FileVoter extends Voter
{
    public const DELETE = 'FILE_DELETE';
    public const VIEW = 'FILE_VIEW';
    public const CREATE = 'FILE_CREATE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::DELETE, self::VIEW, self::CREATE])
            && ($subject instanceof Signalement || 'boolean' === \gettype($subject));
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            if ($user->isSuperAdmin() || (
                $user->isTerritoryAdmin() &&
                $subject instanceof Signalement &&
                $subject->getTerritory() === $user->getTerritory()
            )
            ) {
                return true;
            }
        }

        return match ($attribute) {
            self::DELETE => $this->canDelete($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::CREATE => $this->canCreate($subject, $user),
            default => false,
        };
    }

    private function canDelete(Signalement $signalement, User $user): bool
    {
        return $this->canCreate($signalement, $user);
    }

    private function canCreate(Signalement $signalement, User $user): bool
    {
        return Signalement::STATUS_ACTIVE === $signalement->getStatut() && $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId();
        })->count() > 0;
    }

    private function canView(bool|Signalement $subject, ?User $user = null): bool
    {
        return $subject instanceof Signalement ? $this->checkSignalementPermission($subject, $user) : $subject;
    }

    private function checkSignalementPermission(Signalement $signalement, ?User $user = null): bool
    {
        if (null === $user) {
            return false;
        }

        return $signalement->getAffectations()->filter(
            function (Affectation $affectation) use ($user) {
                return $affectation->getPartner()->getId() === $user->getPartner()->getId();
            }
        )->count() > 0;
    }
}
