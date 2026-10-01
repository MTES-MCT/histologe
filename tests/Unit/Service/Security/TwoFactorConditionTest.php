<?php

namespace App\Tests\Unit\Service\Security;

use App\Entity\User;
use App\Service\Security\TwoFactorCondition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

class TwoFactorConditionTest extends TestCase
{
    public static function provideCases(): \Generator
    {
        yield '2FA désactivée, super admin' => [false, false, User::ROLE_ADMIN, false];
        yield '2FA désactivée, tous rôles, agent' => [false, true, User::ROLE_USER_PARTNER, false];
        yield '2FA activée, super admin' => [true, false, User::ROLE_ADMIN, true];
        yield '2FA activée, admin territoire' => [true, false, User::ROLE_ADMIN_TERRITORY, false];
        yield '2FA activée, agent' => [true, false, User::ROLE_USER_PARTNER, false];
        yield '2FA activée tous rôles, admin territoire' => [true, true, User::ROLE_ADMIN_TERRITORY, true];
        yield '2FA activée tous rôles, agent' => [true, true, User::ROLE_USER_PARTNER, true];
    }

    #[DataProvider('provideCases')]
    public function testShouldPerformTwoFactorAuthentication(
        bool $feature2faEmailEnabled,
        bool $feature2faEmailAllRoles,
        string $role,
        bool $expected,
    ): void {
        $user = (new User())->setRoles([$role]);
        $context = $this->createStub(AuthenticationContextInterface::class);
        $context->method('getUser')->willReturn($user);

        $condition = new TwoFactorCondition($feature2faEmailEnabled, $feature2faEmailAllRoles);

        $this->assertSame($expected, $condition->shouldPerformTwoFactorAuthentication($context));
    }
}
