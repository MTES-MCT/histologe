<?php

namespace App\Security\Voter;

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

        if (self::EDIT_VISITE == $attribute) {
            return $this->canEditVisite($subject, $user);
        }
    }

    private function canEditVisite(Intervention $intervention, User $user): bool
    {
        $signalement = $intervention->getSignalement();
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut()) {
            return false;
        }

        $isUserInPartnerAffectedToVisite = $user->getPartner() === $intervention->getPartner();
        $isUserTerritoryAdminOfSignalementTerritory = $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory();

        return $user->isSuperAdmin() || $isUserInPartnerAffectedToVisite || $isUserTerritoryAdminOfSignalementTerritory;
    }
}
