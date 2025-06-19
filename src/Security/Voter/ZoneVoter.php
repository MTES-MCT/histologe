<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Zone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ZoneVoter extends Voter
{
    public const string MANAGE = 'ZONE_MANAGE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::MANAGE]) && ($subject instanceof Zone);
    }

    /**
     * @param Zone $subject
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
            self::MANAGE => $this->canManage($subject, $user),
            default => false,
        };
    }

    private function canManage(Zone $zone, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->hasPartnerInTerritory($zone->getTerritory())) {
            return true;
        }

        return false;
    }
}
