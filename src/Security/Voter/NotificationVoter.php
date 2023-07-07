<?php

namespace App\Security\Voter;

use App\Entity\Notification;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationVoter extends Voter
{
    public const DELETE = 'NOTIF_DELETE';
    public const MARK_AS_READ = 'NOTIF_MARK_AS_READ';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::DELETE, self::MARK_AS_READ])
            && $subject instanceof Notification;
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
            self::DELETE => $this->canDelete($subject, $user),
            self::MARK_AS_READ => $this->canMarkAsRead($subject, $user),
            default => false,
        };
    }

    private function canDelete(Notification $notification, User $user): bool
    {
        return $user->getId() === $notification->getUser()->getId();
    }

    private function canMarkAsRead(Notification $notification, User $user): bool
    {
        return $user->getId() === $notification->getUser()->getId();
    }
}
