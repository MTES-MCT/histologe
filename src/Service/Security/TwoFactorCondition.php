<?php

namespace App\Service\Security;

use App\Entity\User;
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
    ) {
    }

    /**
     * Les rôles éligibles et l'exclusion ProConnect sont gérés par User::isEmailAuthEnabled().
     * FEATURE_2FA_EMAIL_ALL_ROLES permet de filtrer pour ne garder que les super admin.
     */
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        /**
         * Quand on supprimera FEATURE_2FA_EMAIL_ALL_ROLES
         * Vider et remplacer par
         * return $this->feature2faEmailEnabled;.
         */
        if (!$this->feature2faEmailEnabled) {
            return false;
        }

        if ($this->feature2faEmailAllRoles) {
            return true;
        }

        $user = $context->getUser();

        return $user instanceof User && $user->isSuperAdmin();
    }
}
