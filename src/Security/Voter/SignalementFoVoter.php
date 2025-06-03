<?php

namespace App\Security\Voter;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Security\User\SignalementUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SignalementFoVoter extends Voter
{
    public const string SIGN_USAGER_VIEW = 'SIGN_USAGER_VIEW';
    public const string SIGN_USAGER_EDIT = 'SIGN_USAGER_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::SIGN_USAGER_EDIT, self::SIGN_USAGER_VIEW]) && ($subject instanceof Signalement);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user || !$user instanceof SignalementUser) {
            return false;
        }

        return match ($attribute) {
            self::SIGN_USAGER_VIEW => $this->canUsagerView($subject, $user),
            self::SIGN_USAGER_EDIT => $this->canUsagerEdit($subject, $user),
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
}
