<?php

namespace App\Security\Voter;

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
    public const string SIGN_USAGER_EDIT = 'SIGN_USAGER_EDIT';
    public const string SIGN_USAGER_EDIT_PROCEDURE = 'SIGN_USAGER_EDIT_PROCEDURE';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SIGN_USAGER_EDIT, self::SIGN_USAGER_VIEW, self::SIGN_USAGER_EDIT_PROCEDURE]) && ($subject instanceof Signalement);
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
            self::SIGN_USAGER_EDIT => $this->canUsagerEdit($subject, $user),
            self::SIGN_USAGER_EDIT_PROCEDURE => $this->canUsagerEditProcedure($subject),
            default => false,
        };
    }

    private function canUsagerView(Signalement $signalement, SignalementUser $user): bool
    {
        return $signalement->getCodeSuivi() === $user->getCodeSuivi();
    }

    private function canUsagerEdit(Signalement $signalement, SignalementUser $user): bool
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

    private function canUsagerEditProcedure(Signalement $signalement): bool
    {
        if (SignalementStatus::ACTIVE === $signalement->getStatut()
            || SignalementStatus::NEED_VALIDATION === $signalement->getStatut()
        ) {
            return true;
        }

        return false;
    }
}
