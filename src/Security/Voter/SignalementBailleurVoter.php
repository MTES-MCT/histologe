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
    public const string SIGN_BAILLEUR_SEE_MESSAGERIE = 'SIGN_BAILLEUR_SEE_MESSAGERIE';
    public const string SIGN_BAILLEUR_ADD_SUIVI = 'SIGN_BAILLEUR_ADD_SUIVI';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SIGN_BAILLEUR_ADD_SUIVI, self::SIGN_BAILLEUR_SEE_MESSAGERIE]) && ($subject instanceof Signalement);
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
            self::SIGN_BAILLEUR_SEE_MESSAGERIE => $this->canBailleurSeeMessagerie($subject),
            self::SIGN_BAILLEUR_ADD_SUIVI => $this->canBailleurAddSuivi($subject),
            default => false,
        };
    }

    private function canBailleurSeeMessagerie(Signalement $signalement): bool
    {
        return $signalement->getSuiviReponseBailleur() || SignalementStatus::INJONCTION_BAILLEUR !== $signalement->getStatut();
    }

    private function canBailleurAddSuivi(Signalement $signalement): bool
    {
        if (in_array($signalement->getStatut(), [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE])) {
            return true;
        }

        if ($signalement->getSuiviReponseBailleur() && SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            return true;
        }

        return false;
    }
}
