<?php

namespace App\Security\Voter;

use App\Entity\Intervention;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class InterventionVoter extends Voter
{
    public const EDIT_VISITE = 'INTERVENTION_EDIT_VISITE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT_VISITE])
            && ($subject instanceof Intervention || $subject instanceof ArrayCollection);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::EDIT_VISITE => $this->canEditVisite($subject, $user),
            default => false,
        };
    }

    public function canEditVisite(Intervention $intervention, UserInterface $user): bool
    {
        return $intervention->getPartner()->getId() === $user->getPartner()->getId() || $user->isTerritoryAdmin() && $user->getTerritory() === $intervention->getSignalement()->getTerritory();
    }
}
