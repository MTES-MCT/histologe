<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SuiviVoter extends Voter
{
    public const string CREATE = 'COMMENT_CREATE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE])
            && ($subject instanceof Suivi || $subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isSuperAdmin() && !$user->hasPartnerInTerritory($subject->getTerritory())) {
            $vote?->addReason('L\'utilisateur n\'a pas les droits suffisants dans le territoire demandé.');

            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($subject, $user),
            default => false,
        };
    }

    private function canCreate(Signalement $signalement, User $user): bool
    {
        if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
            return false;
        }
        if ($user->isTerritoryAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        return $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner()->getId() === $partner->getId() && Affectation::STATUS_ACCEPTED == $affectation->getStatut();
        })->count() > 0;
    }
}
