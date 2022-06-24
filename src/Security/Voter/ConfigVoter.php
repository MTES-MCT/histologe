<?php

namespace App\Security\Voter;

use App\Entity\Config;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ConfigVoter extends Voter
{
    public const EDIT = 'CONFIG_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute == self::EDIT
            && $subject instanceof Config;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if($user->isSuperAdmin())
            return true;

        return match ($attribute) {
            self::EDIT => $this->canEdit($user, $subject),
            default => false,
        };

    }

    private function canEdit(UserInterface $user, Config $config): bool
    {
        return $user->isTerritoryAdmin() && $user->getTerritory()->getConfig() === $config;
    }
}
