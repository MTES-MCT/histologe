<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    public const DELETE = 'FILE_DELETE';
    public const VIEW = 'FILE_VIEW';
    public const CREATE = 'FILE_CREATE';
    public const EDIT = 'FILE_EDIT';

    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::DELETE, self::VIEW, self::CREATE, self::EDIT]) && ($subject instanceof Signalement || $subject instanceof File);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Signalement && !$subject instanceof File) {
            return false;
        }
        if (self::DELETE !== $attribute &&
            self::EDIT !== $attribute &&
            $this->isAdminOrRTonHisTerritory($subject, $user)
        ) {
            return true;
        }

        return match ($attribute) {
            self::DELETE => $this->canDelete($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::CREATE => $this->canCreate($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            default => false,
        };
    }

    private function canCreate(Signalement $signalement, User $user): bool
    {
        return Signalement::STATUS_ACTIVE === $signalement->getStatut()
            && $this->checkSignalementPermission($signalement, $user);
    }

    private function canView(Signalement $subject, User $user = null): bool
    {
        return $this->checkSignalementPermission($subject, $user);
    }

    private function canEdit(File $file, User $user): bool
    {
        return $this->canCreate($file->getSignalement(), $user)
            && (
                $this->isFileUploadedByUser($file, $user)
                ||
                $this->isAdminOrRTonHisTerritory($file, $user)
            );
    }

    private function canDelete(File $file, User $user): bool
    {
        return $this->canCreate($file->getSignalement(), $user)
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

    private function isAdminOrRTonHisTerritory(File|Signalement $subject, ?User $user = null): bool
    {
        if (null === $user) {
            return false;
        }

        return $user->isSuperAdmin() ||
        ($user->isTerritoryAdmin() && $this->isOnUserTerritory($subject, $user));
    }

    private function checkSignalementPermission(Signalement $signalement, ?User $user = null): bool
    {
        if (null === $user) {
            return false;
        }
        if ($this->isAdminOrRTonHisTerritory($signalement, $user)) {
            return true;
        }

        return $signalement->getAffectations()->filter(
            function (Affectation $affectation) use ($user) {
                return $affectation->getPartner()->getId() === $user->getPartner()->getId();
            }
        )->count() > 0;
    }

    private function isOnUserTerritory(File|Signalement $subject, User $user): bool
    {
        if (
            (
                $subject instanceof Signalement && $subject->getTerritory() === $user->getTerritory()
            )
            ||
            (
                $subject instanceof File && $subject->getSignalement()->getTerritory() === $user->getTerritory()
            )
        ) {
            return true;
        }

        return false;
    }
}
