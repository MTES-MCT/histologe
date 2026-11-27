<?php

namespace App\Tests\Unit\Service\Security;

use App\Entity\User;
use App\Security\User\SignalementUser;
use App\Service\Security\TwoFactorCondition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

class TwoFactorConditionTest extends TestCase
{
    public static function provideCases(): \Generator
    {
        yield '2FA désactivée, super admin' => [false, false, false, User::ROLE_ADMIN, false];
        yield '2FA désactivée, tous rôles, agent' => [false, true, false, User::ROLE_USER_PARTNER, false];
        yield '2FA désactivée, usager activé' => [false, false, true, User::ROLE_USAGER, false];
        yield '2FA activée, super admin' => [true, false, false, User::ROLE_ADMIN, true];
        yield '2FA activée, admin territoire' => [true, false, false, User::ROLE_ADMIN_TERRITORY, false];
        yield '2FA activée, agent' => [true, false, false, User::ROLE_USER_PARTNER, false];
        yield '2FA activée, usager désactivé' => [true, false, false, User::ROLE_USAGER, false];
        yield '2FA activée, usager activé' => [true, false, true, User::ROLE_USAGER, true];
        yield '2FA activée tous rôles, admin territoire' => [true, true, false, User::ROLE_ADMIN_TERRITORY, true];
        yield '2FA activée tous rôles, agent' => [true, true, false, User::ROLE_USER_PARTNER, true];
        yield '2FA activée tous rôles, usager désactivé' => [true, true, false, User::ROLE_USAGER, false];
        yield '2FA activée tous rôles, usager activé' => [true, true, true, User::ROLE_USAGER, true];
    }

    #[DataProvider('provideCases')]
    public function testShouldPerformTwoFactorAuthentication(
        bool $feature2faEmailEnabled,
        bool $feature2faEmailAllRoles,
        bool $feature2faEmailUsagers,
        string $role,
        bool $expected,
    ): void {
        $user = (new User())->setRoles([$role]);
        if (User::ROLE_USAGER === $role) {
            $user = (new SignalementUser(
                '12345678:occupant',
                $user
            ));
        }
        $context = $this->createStub(AuthenticationContextInterface::class);
        $context->method('getUser')->willReturn($user);

        $condition = new TwoFactorCondition($feature2faEmailEnabled, $feature2faEmailAllRoles, $feature2faEmailUsagers);

        $this->assertSame($expected, $condition->shouldPerformTwoFactorAuthentication($context));
    }
}
