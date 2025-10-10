<?php

namespace App\Security\Voter\Api;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\File;
use App\Entity\User;
use App\Service\Security\PartnerAuthorizedResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiFileVoter extends Voter
{
    public const string API_FILE_UPDATE = 'API_FILE_UPDATE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::API_FILE_UPDATE]) && $subject instanceof File;
    }

    /**
     * @param File $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$this->accessDecisionManager->decide($token, [User::ROLE_API_USER])) {
            return false;
        }
        /** @var User $user */
        $user = $token->getUser();

        return match ($attribute) {
            self::API_FILE_UPDATE => $this->canUpdate($subject, $user),
            default => false,
        };
    }

    private function canUpdate(File $file, User $user): bool
    {
        if (null === $file->getSignalement()) {
            return false;
        }
        $signalement = $file->getSignalement();
        if ($file->getIntervention() && DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType()) {
            return false;
        }
        if (!$file->getUploadedBy()) {
            return false;
        }
        if ($file->getUploadedBy() !== $user) {
            return false;
        }
        // condition ci-dessous pour permettre l'édition de File suite à la création d'un signalement API
        if (
            $signalement->getCreatedBy() === $user
            && $this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $file->getPartner())
            && in_array($signalement->getStatut(), [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE])) {
            return true;
        }
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        if ($file->getPartner()) {
            $affectation = $signalement->getAffectationForPartner($file->getPartner());
            if (!$affectation) {
                return false;
            }
            if (AffectationStatus::ACCEPTED !== $affectation->getStatut()) {
                return false;
            }
            if ($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $affectation->getPartner())) {
                return true;
            }

            return false;
        }

        return false;
    }
}
