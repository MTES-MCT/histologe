<?php

namespace App\Security\Voter\Api;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Security\PartnerAuthorizedResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiSignalementPartnerVoter extends Voter
{
    public const string API_ADD_INTERVENTION = 'API_ADD_INTERVENTION';
    public const string API_ADD_SUIVI = 'API_ADD_SUIVI';
    public const string API_ADD_FILE = 'API_ADD_FILE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::API_ADD_INTERVENTION, self::API_ADD_SUIVI, self::API_ADD_FILE])) {
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

        if (!$this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $subject['partner'])) {
            return false;
        }

        return match ($attribute) {
            self::API_ADD_INTERVENTION => $this->canAddIntervention($subject['signalement'], $subject['partner'], $vote),
            self::API_ADD_SUIVI => $this->canAddSuivi($subject['signalement'], $subject['partner'], $vote),
            self::API_ADD_FILE => $this->canAddFile($subject['signalement'], $subject['partner'], $user, $vote),
            default => false,
        };
    }

    private function canAddIntervention(Signalement $signalement, Partner $partner, ?Vote $vote = null): bool
    {
        if (!$this->canEditSignalement($signalement, $partner, $vote)) {
            return false;
        }
        if (!\in_array(Qualification::VISITES, $partner->getCompetence())) {
            $vote->addReason('Le partenaire n\'a pas la compétence visite.');

            return false;
        }

        return true;
    }

    private function canAddSuivi(Signalement $signalement, Partner $partner, ?Vote $vote = null): bool
    {
        return $this->canEditSignalement($signalement, $partner, $vote);
    }

    private function canAddFile(Signalement $signalement, Partner $partner, User $user, ?Vote $vote = null): bool
    {
        if (
            $signalement->getCreatedByPartner() === $partner
            && $signalement->getCreatedBy() === $user
            && in_array($signalement->getStatut(), [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE])
        ) {
            return true;
        }

        return $this->canEditSignalement($signalement, $partner, $vote);
    }

    private function canEditSignalement(Signalement $signalement, Partner $partner, ?Vote $vote = null): bool
    {
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            $vote->addReason('Le signalement n\'est pas actif.');

            return false;
        }
        $affectation = $signalement->getAffectationForPartner($partner);
        if (!$affectation) {
            $vote->addReason('Le partenaire n\'est pas affecté au signalement.');

            return false;
        }
        if (AffectationStatus::ACCEPTED !== $affectation->getStatut()) {
            $vote->addReason('L\'affectation doit être au statut EN_COURS.');

            return false;
        }

        return true;
    }
}
