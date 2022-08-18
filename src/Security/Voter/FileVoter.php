<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
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
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            if ($user->isSuperAdmin() || $subject instanceof Signalement && $subject->getTerritory() === $user->getTerritory()) {
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

    private function canDelete(Signalement $signalement, UserInterface $user): bool
    {
        return $this->canCreate($signalement, $user);
    }

    private function canCreate(Signalement $signalement, UserInterface $user): bool
    {
        return Signalement::STATUS_ACTIVE === $signalement->getStatut() && $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId();
        })->count() > 0;
    }

    private function canView(bool $authorization, UserInterface|null $user): bool
    {
        return $authorization || $user instanceof UserInterface;
    }
}
