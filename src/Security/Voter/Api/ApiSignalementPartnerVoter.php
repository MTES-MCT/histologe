<?php

namespace App\Security\Voter\Api;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Security\UserApiPermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiSignalementPartnerVoter extends Voter
{
    public const string API_ADD_INTERVENTION = 'API_ADD_INTERVENTION';
    public const string API_EDIT_SIGNALEMENT = 'API_EDIT_SIGNALEMENT';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly UserApiPermissionService $userApiPermissionService,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::API_ADD_INTERVENTION, self::API_EDIT_SIGNALEMENT])) {
            return false;
        }
        if (!is_array($subject)) {
            return false;
        }
        if (!isset($subject['signalement']) || !isset($subject['partner'])) {
            return false;
        }
        if (!$subject['signalement'] instanceof Signalement || !$subject['partner'] instanceof Partner) {
            return false;
        }

        return true;
    }

    /**
     * @param array{signalement: Signalement, partner: Partner} $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$this->accessDecisionManager->decide($token, [User::ROLE_API_USER])) {
            return false;
        }
        /** @var User $user */
        $user = $token->getUser();

        if (!$this->userApiPermissionService->hasPermissionOnPartner($user, $subject['partner'])) {
            return false;
        }

        return match ($attribute) {
            self::API_ADD_INTERVENTION => $this->canAddIntervention($subject['signalement'], $subject['partner']),
            self::API_EDIT_SIGNALEMENT => $this->canEditSignalement($subject['signalement'], $subject['partner']),
            default => false,
        };
    }

    private function canAddIntervention(Signalement $signalement, Partner $partner): bool
    {
        if (!$this->canEditSignalement($signalement, $partner)) {
            return false;
        }
        if (!\in_array(Qualification::VISITES, $partner->getCompetence())) {
            return false;
        }

        return true;
    }

    private function canEditSignalement(Signalement $signalement, Partner $partner): bool
    {
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        $affectation = $signalement->getAffectationForPartner($partner);
        if (!$affectation) {
            return false;
        }
        if (AffectationStatus::ACCEPTED !== $affectation->getStatut()) {
            return false;
        }

        return true;
    }
}
