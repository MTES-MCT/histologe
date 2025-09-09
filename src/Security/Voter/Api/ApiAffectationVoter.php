<?php

namespace App\Security\Voter\Api;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Service\Security\PartnerAuthorizedResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiAffectationVoter extends Voter
{
    public const string API_AFFECTATION_UPDATE = 'API_AFFECTATION_UPDATE';
    public const string API_AFFECTATION_UPDATE_STATUS = 'API_AFFECTATION_UPDATE_STATUS';

    /** @var array<string, array<string>> */
    private const array VALID_WORKFLOW_STATUS = [
        AffectationStatus::WAIT->value => [AffectationStatus::ACCEPTED->value, AffectationStatus::REFUSED->value],
        AffectationStatus::ACCEPTED->value => [AffectationStatus::CLOSED->value],
        AffectationStatus::REFUSED->value => [AffectationStatus::ACCEPTED->value],
        AffectationStatus::CLOSED->value => [AffectationStatus::WAIT->value],
    ];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::API_AFFECTATION_UPDATE, self::API_AFFECTATION_UPDATE_STATUS]) && $subject instanceof Affectation;
    }

    /**
     * @param Affectation $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$this->accessDecisionManager->decide($token, [User::ROLE_API_USER])) {
            return false;
        }
        /** @var User $user */
        $user = $token->getUser();

        return match ($attribute) {
            self::API_AFFECTATION_UPDATE => $this->canUpdate($subject, $user),
            self::API_AFFECTATION_UPDATE_STATUS => $this->canUpdateStatus($subject, $user),
            default => false,
        };
    }

    private function canUpdate(Affectation $affectation, User $user): bool
    {
        if (SignalementStatus::ACTIVE !== $affectation->getSignalement()->getStatut()) {
            return false;
        }

        return $this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $affectation->getPartner());
    }

    private function canUpdateStatus(Affectation $affectation, User $user): bool
    {
        $newStatus = $affectation->getNextStatut();
        $previousStatus = $affectation->getStatut();

        return isset(self::VALID_WORKFLOW_STATUS[$previousStatus->value]) && in_array($newStatus->value, self::VALID_WORKFLOW_STATUS[$previousStatus->value], true);
    }
}
