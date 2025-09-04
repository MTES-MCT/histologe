<?php

namespace App\Security\Voter\Api;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\File;
use App\Entity\User;
use App\Service\Security\UserApiPermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiFileVoter extends Voter
{
    public const string API_FILE_UPDATE = 'API_FILE_UPDATE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly UserApiPermissionService $userApiPermissionService,
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
        if ($file->getIntervention() && DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType()) {
            return false;
        }
        if (SignalementStatus::ACTIVE !== $file->getSignalement()->getStatut()) {
            return false;
        }
        if (!$file->getUploadedBy()) {
            return false;
        }
        if ($file->getUploadedBy() !== $user) {
            return false;
        }
        // TODO permission API : Une fois que le champ partner_id de File sera renseigné se baser dessus
        // on vérifiera aussi le statut de l'affectation bien que ce n'était pas le cas jusqu'à présent
        if (1 === 2) { // @phpstan-ignore-line
            $affectation = $file->getSignalement()->getAffectationForPartner($file->getPartner());
            if (!$affectation) {
                return false;
            }
            if (AffectationStatus::ACCEPTED !== $affectation->getStatut()) {
                return false;
            }
            if ($this->userApiPermissionService->hasPermissionOnPartner($user, $affectation->getPartner())) {
                return true;
            }

            return false;
        }
        $hasAffectationForPartner = false;
        foreach ($file->getSignalement()->getAffectations() as $affectation) {
            if ($this->userApiPermissionService->hasPermissionOnPartner($user, $affectation->getPartner())) {
                $hasAffectationForPartner = true;
                break;
            }
        }
        if (!$hasAffectationForPartner) {
            return false;
        }

        return true;
    }
}
