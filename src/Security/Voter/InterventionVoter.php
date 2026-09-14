<?php

namespace App\Security\Voter;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Intervention>
 */
class InterventionVoter extends Voter
{
    public const string INTERVENTION_EDIT_VISITE = 'INTERVENTION_EDIT_VISITE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::INTERVENTION_EDIT_VISITE]) && ($subject instanceof Intervention);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié');

            return false;
        }

        return match ($attribute) {
            self::INTERVENTION_EDIT_VISITE => self::canEditVisite($subject, $user),
            default => false,
        };
    }

    public static function canEditVisite(Intervention $intervention, User $user): bool
    {
        // TODO : Refonte visites
        // - bloquer les édition des intervention de type ARRETE_PREFECTORAL

        $signalement = $intervention->getSignalement();
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTerritoryAdmin() && $user->hasPartnerInTerritory($signalement->getAddress()->getTerritory())) {
            return true;
        }
        $partner = $user->getPartnerInTerritory($signalement->getAddress()->getTerritory());
        if (!$intervention->getPartner()->getId() || $intervention->getPartner()->getId() != $partner->getId()) {
            return false;
        }
        if (!$partner || !in_array(Qualification::VISITES, $partner->getCompetence())) {
            return false;
        }
        if (!$signalement->getAffectationForPartner($partner) || AffectationStatus::ACCEPTED !== $signalement->getAffectationForPartner($partner)->getStatut()) {
            return false;
        }

        return true;
    }
}
