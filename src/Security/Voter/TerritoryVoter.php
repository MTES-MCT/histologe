<?php

namespace App\Security\Voter;

use App\Entity\Territory;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TerritoryVoter extends Voter
{
    public const GET_DOCUMENT = 'GET_DOCUMENT';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::GET_DOCUMENT]) && ($subject instanceof Territory);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::GET_DOCUMENT => $this->isInTerritory($subject, $user),
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
}
