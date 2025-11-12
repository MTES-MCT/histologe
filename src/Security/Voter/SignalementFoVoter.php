<?php

namespace App\Security\Voter;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Security\User\SignalementUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SignalementFoVoter extends Voter
{
    public const string SIGN_USAGER_VIEW = 'SIGN_USAGER_VIEW';
    public const string SIGN_USAGER_ADD_SUIVI = 'SIGN_USAGER_ADD_SUIVI';
    public const string SIGN_USAGER_EDIT = 'SIGN_USAGER_EDIT';
    public const string SIGN_USAGER_BASCULE_PROCEDURE = 'SIGN_USAGER_BASCULE_PROCEDURE';
    public const string SIGN_USAGER_COMPLETE = 'SIGN_USAGER_COMPLETE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [
            self::SIGN_USAGER_ADD_SUIVI,
            self::SIGN_USAGER_VIEW,
            self::SIGN_USAGER_EDIT,
            self::SIGN_USAGER_BASCULE_PROCEDURE,
            self::SIGN_USAGER_COMPLETE,
        ])
            && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user instanceof SignalementUser) {
            $vote?->addReason('L\'usager n\'est pas authentifié');

            return false;
        }

        return match ($attribute) {
            self::SIGN_USAGER_VIEW => $this->canUsagerView($subject, $user),
            self::SIGN_USAGER_ADD_SUIVI => $this->canUsagerAddSuivi($subject, $user),
            self::SIGN_USAGER_EDIT => $this->canUsagerEdit($subject),
            self::SIGN_USAGER_BASCULE_PROCEDURE => $this->canUsagerBasculeProcedure($subject, $user),
            self::SIGN_USAGER_COMPLETE => $this->canUsagerCompleteDossier($subject),
            default => false,
        };
    }

    private function canUsagerView(Signalement $signalement, SignalementUser $user): bool
    {
        return $signalement->getCodeSuivi() === $user->getCodeSuivi();
    }

    private function canUsagerAddSuivi(Signalement $signalement, SignalementUser $user): bool
    {
        if (!$this->canUsagerView($signalement, $user)) {
            return false;
        }
        if (in_array($signalement->getStatut(), [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE, SignalementStatus::CLOSED])) {
            if (SignalementStatus::CLOSED !== $signalement->getStatut()) {
                return true;
            }
            if (!$signalement->getClosedAt()) {
                return false;
            }
            $datePostCloture = $signalement->getClosedAt()->modify('+ 30days');
            $today = new \DateTimeImmutable();
            if ($today < $datePostCloture && !$signalement->hasSuiviUsagerPostCloture()) {
                return true;
            }
        }

        return false;
    }

    private function canUsagerEdit(Signalement $signalement): bool
    {
        if (in_array($signalement->getStatut(), [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::INJONCTION_BAILLEUR,
        ])) {
            return true;
        }

        return false;
    }

    private function canUsagerBasculeProcedure(Signalement $signalement, SignalementUser $user): bool
    {
        if ($this->canUsagerView($signalement, $user) && SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            return true;
        }

        return false;
    }

    private function canUsagerCompleteDossier(Signalement $signalement): bool
    {
        // Pour l'instant, on ne peut compléter que les infos du bailleur, donc on filtre sur le profil
        if (
            $this->canUsagerEdit($signalement)
            && ProfileDeclarant::BAILLEUR !== $signalement->getProfileDeclarant()
            && ProfileDeclarant::BAILLEUR_OCCUPANT !== $signalement->getProfileDeclarant()
        ) {
            return true;
        }

        return false;
    }
}
