<?php

namespace App\Security\Voter;

use App\Entity\Arrete;
use App\Entity\Enum\ArreteCreationSource;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Arrete>
 */
class ArreteVoter extends Voter
{
    public const string ARRETE_DELETE = 'ARRETE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::ARRETE_DELETE]) && ($subject instanceof Arrete);
    }

    /**
     * @param Arrete $subject
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
            self::ARRETE_DELETE => $this->canDelete($subject, $user, $vote),
            default => false,
        };
    }

    private function canDelete(Arrete $arrete, User $user, ?Vote $vote): bool
    {
        if (ArreteCreationSource::ESABORA === $arrete->getArreteCreationSource()) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY') && $user->hasPartnerInTerritory($arrete->getAddress()->getTerritory())) {
            return true;
        }

        return false;
    }
}
