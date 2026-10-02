<?php

namespace App\Security\Voter;

use App\Entity\AutoAffectationRule;
use App\Entity\User;
use App\Entity\Zone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Zone>
 */
class ZoneVoter extends Voter
{
    public const string ZONE_MANAGE = 'ZONE_MANAGE';
    public const string ZONE_DELETE = 'ZONE_DELETE';
    public const string ZONE_DELETE_DENIED_MESSAGE = 'Cette zone ne peut pas être supprimée car elle est utilisée par une règle d\'auto-affectation active. Merci de contacter les administrateurs pour faire modifier cette règle avant de supprimer la zone.';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::ZONE_MANAGE, self::ZONE_DELETE]) && ($subject instanceof Zone);
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
            self::ZONE_MANAGE => $this->canManage($subject, $user),
            self::ZONE_DELETE => $this->canDelete($subject, $user, $vote),
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

    private function canDelete(Zone $zone, User $user, ?Vote $vote): bool
    {
        if (!$this->canManage($zone, $user)) {
            return false;
        }
        foreach ($zone->getTerritory()->getAutoAffectationRules() as $rule) {
            if (AutoAffectationRule::STATUS_ACTIVE !== $rule->getStatus()) {
                continue;
            }
            $ruleZoneIds = array_map('intval', array_merge($rule->getZoneToInclude() ?? [], $rule->getZoneToExclude() ?? []));
            if (\in_array($zone->getId(), $ruleZoneIds, true)) {
                $vote?->addReason(self::ZONE_DELETE_DENIED_MESSAGE);

                return false;
            }
        }

        return true;
    }
}
