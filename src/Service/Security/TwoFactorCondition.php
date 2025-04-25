<?php

namespace App\Service\Security;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RequestContext;

class TwoFactorCondition implements TwoFactorConditionInterface
{
    public function __construct(
        private readonly RequestContext $requestContext,
        #[Autowire(env: 'FEATURE_2FA_EMAIL_ENABLED')]
        private bool $feature2faEmailEnabled,
        #[Autowire(env: 'APP_URL')]
        private readonly string $appUrl,
    ) {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        if (!$this->isLocalEnvironment()) {
            $this->requestContext->setScheme('https');
        }

        return $this->feature2faEmailEnabled;
    }

    private function isLocalEnvironment(): bool
    {
        return str_contains($this->appUrl, 'localhost');
    }
}
