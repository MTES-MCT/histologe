<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class InterventionVoter extends Voter
{
    public const EDIT_VISITE = 'INTERVENTION_EDIT_VISITE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT_VISITE]) && ($subject instanceof Intervention);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::EDIT_VISITE => $this->canEditVisite($subject, $user),
            default => false,
        };
    }

    private function canEditVisite(Intervention $intervention, User $user): bool
    {
        $signalement = $intervention->getSignalement();
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut()) {
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
                && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
        })->count() > 0;
        $isUserInPartnerAffectedToVisite = $partner === $intervention->getPartner() && $isUserInAffectedPartnerWithQualificationVisite;
        $isUserTerritoryAdminOfSignalementTerritory = $user->isTerritoryAdmin();

        return $isUserInPartnerAffectedToVisite || $isUserTerritoryAdminOfSignalementTerritory;
    }
}
