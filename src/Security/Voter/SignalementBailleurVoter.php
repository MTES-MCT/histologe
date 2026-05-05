<?php

namespace App\Security\Voter;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Security\User\SignalementBailleur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Signalement>
 */
class SignalementBailleurVoter extends Voter
{
    public const string SIGN_BAILLEUR_HAS_RESPONSE_INJONCTION = 'SIGN_BAILLEUR_HAS_RESPONSE_INJONCTION';
    public const string SIGN_BAILLEUR_ADD_SUIVI = 'SIGN_BAILLEUR_ADD_SUIVI';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SIGN_BAILLEUR_ADD_SUIVI, self::SIGN_BAILLEUR_HAS_RESPONSE_INJONCTION]) && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user instanceof SignalementBailleur) {
            $vote?->addReason('Le bailleur n\'est pas authentifié');

            return false;
        }

        return match ($attribute) {
            self::SIGN_BAILLEUR_HAS_RESPONSE_INJONCTION => $this->isSignalementHasResponseInjonction($subject),
            self::SIGN_BAILLEUR_ADD_SUIVI => $this->canBailleurAddSuivi($subject),
            default => false,
        };
    }

    private function isSignalementHasResponseInjonction(Signalement $signalement): bool
    {
        return $signalement->getSuiviReponseBailleur() ? true : false;
    }

    private function canBailleurAddSuivi(Signalement $signalement): bool
    {
        if (in_array($signalement->getStatut(), [
            SignalementStatus::CLOSED,
            SignalementStatus::ARCHIVED,
            SignalementStatus::REFUSED,
            SignalementStatus::INJONCTION_CLOSED,
        ], true)) {
            return false;
        }

        // autorisé si le bailleur a répondu à l'injonction et que le signalement est encore en cours, y compris aprés bascule de procédure
        return $this->isSignalementHasResponseInjonction($signalement);
    }
}
