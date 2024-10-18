<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Zone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ZoneVoter extends Voter
{
    public const MANAGE = 'ZONE_MANAGE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::MANAGE]) && ($subject instanceof Zone);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE => $this->canManage($subject, $user),
            default => false,
        };
    }

    private function canManage(Zone $zone, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->getPartner()->getTerritory()->getId() === $zone->getTerritory()->getId()) {
            return true;
        }

        return false;
    }
}
