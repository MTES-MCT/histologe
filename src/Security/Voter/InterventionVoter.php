<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class InterventionVoter extends Voter
{
    public const EDIT_VISITE = 'INTERVENTION_EDIT_VISITE';
    public const EDIT_VISITE_PARTNER = 'INTERVENTION_EDIT_VISITE_PARTNER';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::EDIT_VISITE, self::EDIT_VISITE_PARTNER])
            && ($subject instanceof Intervention || $subject instanceof ArrayCollection);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::EDIT_VISITE => $this->canEditVisite($subject, $user),
            self::EDIT_VISITE_PARTNER => $this->canEditVisitePartner($subject, $user),
            default => false,
        };
    }

    public function canEditVisite(Intervention $intervention, UserInterface $user): bool
    {
        $signalement = $intervention->getSignalement();
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut() && Signalement::STATUS_NEED_PARTNER_RESPONSE !== $signalement->getStatut()) {
            return false;
        }

        $isUserInAffectedPartnerWithQualificationVisite = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId()
                && \in_array(Qualification::VISITES, $user->getPartner()->getCompetence())
                && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
        })->count() > 0;

        return $isUserInAffectedPartnerWithQualificationVisite
        || $user->isTerritoryAdmin() && $user->getTerritory() === $signalement->getTerritory()
        || $user->isSuperAdmin();
    }

    public function canEditVisitePartner(Intervention $intervention, UserInterface $user): bool
    {
        $signalement = $intervention->getSignalement();
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut() && Signalement::STATUS_NEED_PARTNER_RESPONSE !== $signalement->getStatut()) {
            return false;
        }

        $isUserInAffectedPartnerWithQualificationVisite = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner()->getId() === $user->getPartner()->getId()
                && \in_array(Qualification::VISITES, $user->getPartner()->getCompetence())
                && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
        })->count() > 0;

        return $isUserInAffectedPartnerWithQualificationVisite
        || $user->isTerritoryAdmin() && $user->getTerritory() === $intervention->getSignalement()->getTerritory()
        || $user->isSuperAdmin();
    }
}
