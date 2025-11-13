<?php

namespace App\Security\Voter\Api;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\User;
use App\Service\Security\PartnerAuthorizedResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Intervention>
 */
class ApiInterventionVoter extends Voter
{
    public const string API_INTERVENTION_UPDATE = 'API_INTERVENTION_UPDATE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::API_INTERVENTION_UPDATE]) && $subject instanceof Intervention;
    }

    /**
     * @param Intervention $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$this->accessDecisionManager->decide($token, [User::ROLE_API_USER])) {
            return false;
        }
        /** @var User $user */
        $user = $token->getUser();

        return match ($attribute) {
            self::API_INTERVENTION_UPDATE => $this->canUpdate($subject, $user),
            default => false,
        };
    }

    private function canUpdate(Intervention $intervention, User $user): bool
    {
        if (SignalementStatus::ACTIVE !== $intervention->getSignalement()->getStatut()) {
            return false;
        }
        $affectation = $intervention->getSignalement()->getAffectationForPartner($intervention->getPartner());
        if (!$affectation) {
            return false;
        }
        if (AffectationStatus::ACCEPTED !== $affectation->getStatut()) {
            return false;
        }
        if (!\in_array(Qualification::VISITES, $intervention->getPartner()->getCompetence())) {
            return false;
        }

        return $this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $intervention->getPartner());
    }
}
