<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\File;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, File>
 */
class FileVoter extends Voter
{
    public const string FILE_DELETE = 'FILE_DELETE';
    public const string FILE_EDIT = 'FILE_EDIT';
    public const string FILE_FRONT_DELETE = 'FILE_FRONT_DELETE';
    public const string FILE_EDIT_DOCUMENT = 'FILE_EDIT_DOCUMENT';
    public const string FILE_DELETE_DOCUMENT = 'FILE_DELETE_DOCUMENT';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::FILE_DELETE, self::FILE_EDIT, self::FILE_FRONT_DELETE, self::FILE_EDIT_DOCUMENT, self::FILE_DELETE_DOCUMENT])
            && $subject instanceof File;
    }

    /**
     * @param File $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::FILE_FRONT_DELETE === $attribute) {
            return $this->canFrontDelete($subject);
        }

        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié');

            return false;
        }

        return match ($attribute) {
            self::FILE_DELETE => $this->canDelete($subject, $user),
            self::FILE_EDIT => $this->canEdit($subject, $user),
            self::FILE_EDIT_DOCUMENT => $this->canEditDocument($subject, $user),
            self::FILE_DELETE_DOCUMENT => $this->canDeleteDocument($subject, $user),
            default => false,
        };
    }

    private function canFrontDelete(File $file): bool
    {
        return $file->isUsagerFile();
    }

    private function canCreate(File $file, User $user): bool
    {
        if (SignalementStatus::CLOSED === $file->getSignalement()->getStatut() && $this->isAdminOrRTonHisTerritory($file, $user)) {
            return true;
        }
        if (SignalementStatus::DRAFT === $file->getSignalement()->getStatut() && $file->getSignalement()->getCreatedBy() === $user) {
            return true;
        }
        if (SignalementStatus::NEED_VALIDATION === $file->getSignalement()->getStatut() && $user->isSuperAdmin()) {
            return true;
        }
        if (!in_array($file->getSignalement()->getStatut(), [SignalementStatus::ACTIVE, SignalementStatus::INJONCTION_BAILLEUR])) {
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
        if ($file->getIntervention() && DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType()) {
            return false;
        }

        return $this->canCreate($file, $user) && ($this->isFileUploadedByUser($file, $user) || $this->isAdminOrRTonHisTerritory($file, $user));
    }

    private function canDelete(File $file, User $user): bool
    {
        if ($file->getIntervention() && DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType()) {
            return InterventionVoter::canEditVisite($file->getIntervention(), $user);
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->canCreate($file, $user) && ($this->isFileUploadedByUser($file, $user) || $this->isPartnerFileDeletableByAdmin($file, $user));
    }

    private function canEditDocument(File $file, User $user): bool
    {
        return $this->canDeleteDocument($file, $user);
    }

    private function canDeleteDocument(File $file, User $user): bool
    {
        if (!$file->getIsStandalone()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($file->getTerritory() && $user->isTerritoryAdmin() && $user->hasPartnerInTerritory($file->getTerritory())) {
            return true;
        }

        return false;
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
