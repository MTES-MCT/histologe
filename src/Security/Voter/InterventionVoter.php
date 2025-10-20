<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
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
    public const string EDIT_VISITE = 'INTERVENTION_EDIT_VISITE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT_VISITE]) && ($subject instanceof Intervention);
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
            self::EDIT_VISITE => self::canEditVisite($subject, $user),
            default => false,
        };
    }

    public static function canEditVisite(Intervention $intervention, User $user): bool
    {
        $signalement = $intervention->getSignalement();
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        $partner = $user->getPartnerInTerritory($signalement->getTerritory());
        if (!$partner) {
            return false;
        }
        $isUserInAffectedPartnerWithQualificationVisite = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner->getId()
                && \in_array(Qualification::VISITES, $partner->getCompetence())
                && AffectationStatus::ACCEPTED == $affectation->getStatut();
        })->count() > 0;
        $isUserInPartnerAffectedToVisite = $partner === $intervention->getPartner() && $isUserInAffectedPartnerWithQualificationVisite;
        $isUserTerritoryAdminOfSignalementTerritory = $user->isTerritoryAdmin();

        return $isUserInPartnerAffectedToVisite || $isUserTerritoryAdminOfSignalementTerritory;
    }
}
