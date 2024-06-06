<?php

namespace App\Service\Security;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TwoFactorCondition implements TwoFactorConditionInterface
{
    public function __construct(
        #[Autowire(env: 'FEATURE_2FA_EMAIL_ENABLED')]
        private bool $feature2faEmailEnabled,
        ) {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        return $this->feature2faEmailEnabled;
    }
}
