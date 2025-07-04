<?php

namespace App\Security\Voter;

use App\Entity\Territory;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TerritoryVoter extends Voter
{
    public const string GET_DOCUMENT = 'GET_DOCUMENT';
    public const string GET_BAILLEURS_LIST = 'GET_BAILLEURS_LIST';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::GET_DOCUMENT, self::GET_BAILLEURS_LIST]) && ($subject instanceof Territory);
    }

    /**
     * @param Territory $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié');

            return false;
        }

        return match ($attribute) {
            self::GET_DOCUMENT => $this->isInTerritory($subject, $user),
            self::GET_BAILLEURS_LIST => $this->canGetBailleursList($subject, $user),
            default => false,
        };
    }

    private function isInTerritory(Territory $territory, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN') || $user->hasPartnerInTerritory($territory)) {
            return true;
        }

        return false;
    }

    private function canGetBailleursList(Territory $territory, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')
            || ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->hasPartnerInTerritory($territory))) {
            return true;
        }

        return false;
    }
}
