<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
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

    public function canEditVisite(Intervention $intervention, User $user): bool
    {
        $signalement = $intervention->getSignalement();
        if (
            Signalement::STATUS_ACTIVE !== $signalement->getStatut() &&
            Signalement::STATUS_NEED_PARTNER_RESPONSE !== $signalement->getStatut()
        ) {
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
}
