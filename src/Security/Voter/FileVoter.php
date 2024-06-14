<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    public const DELETE = 'FILE_DELETE';
    public const EDIT = 'FILE_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::DELETE, self::EDIT]) && $subject instanceof File;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::DELETE => $this->canDelete($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            default => false,
        };
    }

    private function canCreate(File $file, User $user): bool
    {
        if (Signalement::STATUS_ACTIVE !== $file->getSignalement()->getStatut()) {
            return false;
        }
        if ($this->isAdminOrRTonHisTerritory($file, $user)) {
            return true;
        }

        return $file->getSignalement()->getAffectations()->filter(
            function (Affectation $affectation) use ($user) {
                return $affectation->getPartner()->getId() === $user->getPartner()->getId();
            }
        )->count() > 0;
    }

    private function canEdit(File $file, User $user): bool
    {
        return $this->canCreate($file, $user)
            && (
                $this->isFileUploadedByUser($file, $user)
                ||
                $this->isAdminOrRTonHisTerritory($file, $user)
            );
    }

    private function canDelete(File $file, User $user): bool
    {
        return $this->canCreate($file, $user)
            && (
                $this->isFileUploadedByUser($file, $user)
                ||
                $this->isPartnerFileDeletableByAdmin($file, $user)
            );
    }

    private function isPartnerFileDeletableByAdmin(File $file, User $user): bool
    {
        return $file->isPartnerFile() && $this->isAdminOrRTonHisTerritory($file, $user);
    }

    private function isFileUploadedByUser(File $file, User $user): bool
    {
        return null !== $file->getUploadedBy() && $file->getUploadedBy() === $user;
    }

    private function isAdminOrRTonHisTerritory(File $subject, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTerritoryAdmin() && $subject->getSignalement()->getTerritory() === $user->getTerritory()) {
            return true;
        }

        return false;
    }
}
