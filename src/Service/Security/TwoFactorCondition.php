<?php

namespace App\Service\Security;

use App\Entity\User;
use App\Security\User\SignalementUser;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TwoFactorCondition implements TwoFactorConditionInterface
{
    public function __construct(
        #[Autowire(env: 'FEATURE_2FA_EMAIL_ENABLED')]
        private bool $feature2faEmailEnabled,
        #[Autowire(env: 'FEATURE_2FA_EMAIL_ALL_ROLES')]
        private bool $feature2faEmailAllRoles,
        #[Autowire(env: 'FEATURE_2FA_EMAIL_USAGERS')]
        private bool $feature2faEmailUsagers,
    ) {
    }

    /**
     * Les rôles éligibles et l'exclusion ProConnect sont gérés par User::isEmailAuthEnabled().
     * FEATURE_2FA_EMAIL_ALL_ROLES permet de filtrer pour ne garder que les super admin.
     * FEATURE_2FA_EMAIL_USAGERS active la 2FA des usagers (SignalementUser), indépendamment des autres flags.
     */
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        /**
         * Quand on supprimera FEATURE_2FA_EMAIL_ALL_ROLES,
         * conserver la branche SignalementUser et remplacer le reste par
         * return $this->feature2faEmailEnabled;.
         */
        if (!$this->feature2faEmailEnabled) {
            return false;
        }

        $user = $context->getUser();

        if ($user instanceof SignalementUser) {
            return $this->isEnabledForUsagers();
        }

        if ($this->feature2faEmailAllRoles) {
            return true;
        }

        return $user instanceof User && $user->isSuperAdmin();
    }

    public function isEnabledForUsagers(): bool
    {
        return $this->feature2faEmailEnabled && $this->feature2faEmailUsagers;
    }
}
