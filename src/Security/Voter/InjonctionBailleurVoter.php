<?php

namespace App\Security\Voter;

use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, string>
 */
class InjonctionBailleurVoter extends Voter
{
    public const string INJONCTION_BAILLEUR_SEE = 'INJONCTION_BAILLEUR_SEE';

    public function __construct(
        #[Autowire(env: 'FEATURE_INJONCTION_BAILLEUR')]
        private readonly bool $featureInjonctionBailleur,
        #[Autowire(env: 'FEATURE_INJONCTION_BAILLEUR_DEPTS')]
        private readonly string $featureInjonctionBailleurDepts,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::INJONCTION_BAILLEUR_SEE === $attribute && !$subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        return match ($attribute) {
            self::INJONCTION_BAILLEUR_SEE => $this->canSeeInjonctionBailleur($user),
            default => false,
        };
    }

    private function canSeeInjonctionBailleur(User $user): bool
    {
        if (!$this->featureInjonctionBailleur) {
            return false;
        }
        $arrayDepts = json_decode($this->featureInjonctionBailleurDepts, true);

        return $user->isSuperAdmin()
            || ($user->isTerritoryAdmin() && in_array($user->getFirstTerritory()->getZip(), $arrayDepts))
            || count($user->getPartners()->filter(function (Partner $partner) use ($arrayDepts) {
                return $partner->hasCompetence(Qualification::AIDE_BAILLEURS) && in_array($partner->getTerritory()->getZip(), $arrayDepts);
            }));
    }
}
