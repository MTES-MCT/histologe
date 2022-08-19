<?php

namespace App\Security\Voter;

use App\Entity\Tag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TagVoter extends Voter
{
    public const CREATE = 'TAG_CREATE';
    public const DELETE = 'TAG_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::DELETE])
            && ($subject instanceof Tag || !$subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canCreate($subject, UserInterface $user): bool
    {
        return $user->isTerritoryAdmin();
    }

    private function canDelete(Tag $tag, UserInterface $user): bool
    {
        return $this->canCreate($tag, $user) && $tag->getTerritory() === $user->getTerritory();
    }
}
