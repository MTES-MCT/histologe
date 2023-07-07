<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviVoter extends Voter
{
    public const CREATE = 'COMMENT_CREATE';
    public const VIEW = 'COMMENT_VIEW';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::VIEW])
            && ($subject instanceof Suivi || $subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface || !$user->isSuperAdmin() && $subject->getTerritory() !== $user->getTerritory()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            default => false,
        };
    }

    private function canCreate(Signalement $signalement, User $user): bool
    {
        return Signalement::STATUS_ACTIVE === $signalement->getStatut() && $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId();
        })->count() > 0 || $user->isTerritoryAdmin();
    }

    private function canView(mixed $comment, User $user): bool
    {
        if ($this->canCreate($comment->getSignalement(), $user)) {
            return true;
        }

        return true;
    }
}
