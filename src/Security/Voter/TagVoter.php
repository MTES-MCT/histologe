<?php

namespace App\Security\Voter;

use App\Entity\Tag;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Tag>
 */
class TagVoter extends Voter
{
    public const string TAG_CREATE = 'TAG_CREATE';
    public const string TAG_EDIT = 'TAG_EDIT';
    public const string TAG_DELETE = 'TAG_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::TAG_CREATE, self::TAG_EDIT, self::TAG_DELETE])
            && ($subject instanceof Tag || !$subject);
    }

    /**
     * @param Tag $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié');

            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::TAG_CREATE => $this->canCreate($user),
            self::TAG_EDIT => $this->canEdit($subject, $user),
            self::TAG_DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canCreate(User $user): bool
    {
        return $user->isTerritoryAdmin();
    }

    private function canEdit(Tag $tag, User $user): bool
    {
        return $this->canDelete($tag, $user);
    }

    private function canDelete(Tag $tag, User $user): bool
    {
        return $this->canCreate($user) && $user->hasPartnerInTerritory($tag->getTerritory());
    }
}
