<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\SignalementStatus;
use App\Entity\File;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    public const DELETE = 'FILE_DELETE';
    public const EDIT = 'FILE_EDIT';
    public const FRONT_DELETE = 'FRONT_FILE_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return (\in_array($attribute, [self::DELETE, self::EDIT]) && $subject instanceof File)
            || (self::FRONT_DELETE === $attribute && \is_array($subject) && isset($subject['file'], $subject['email']));
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (self::FRONT_DELETE === $attribute) {
            return $this->canFrontDelete($subject);
        }

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

    private function canFrontDelete(array $subject): bool
    {
        /** @var File $file */
        $file = $subject['file'];
        if (!$file instanceof File) {
            return false;
        }
        $email = $subject['email'];

        return $file->getUploadedBy()?->getEmail() === $email;
    }

    private function canCreate(File $file, User $user): bool
    {
        if (SignalementStatus::CLOSED === $file->getSignalement()->getStatut() && $this->isAdminOrRTonHisTerritory($file, $user)) {
            return true;
        }
        if (SignalementStatus::DRAFT === $file->getSignalement()->getStatut() && $file->getSignalement()->getCreatedBy() === $user) {
            return true;
        }
        if (SignalementStatus::ACTIVE !== $file->getSignalement()->getStatut()) {
            return false;
        }
        if ($this->isAdminOrRTonHisTerritory($file, $user)) {
            return true;
        }
        $partner = $user->getPartnerInTerritory($file->getSignalement()->getTerritory());

        return $file->getSignalement()->getAffectations()->filter(
            function (Affectation $affectation) use ($partner) {
                return $affectation->getPartner()->getId() === $partner->getId();
            }
        )->count() > 0;
    }

    private function canEdit(File $file, User $user): bool
    {
        return $this->canCreate($file, $user)
            && (
                $this->isFileUploadedByUser($file, $user)
                || $this->isAdminOrRTonHisTerritory($file, $user)
            );
    }

    private function canDelete(File $file, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->canCreate($file, $user)
            && (
                $this->isFileUploadedByUser($file, $user)
                || $this->isPartnerFileDeletableByAdmin($file, $user)
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
        if ($user->isTerritoryAdmin() && $user->hasPartnerInTerritory($subject->getSignalement()->getTerritory())) {
            return true;
        }

        return false;
    }
}
