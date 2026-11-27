<?php

namespace App\Tests\Unit\Security\User;

use App\Entity\User;
use App\Security\User\SignalementUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SignalementUserTest extends TestCase
{
    public static function provideEmailAuthCases(): \Generator
    {
        yield 'usager avec e-mail' => [User::ROLE_USAGER, 'occupant@example.com', true];
        yield 'usager sans e-mail' => [User::ROLE_USAGER, null, false];
        yield 'usager e-mail vide' => [User::ROLE_USAGER, '', false];
        yield 'compte pro réutilisé avec e-mail' => [User::ROLE_USER_PARTNER, 'agent@example.com', true];
    }

    #[DataProvider('provideEmailAuthCases')]
    public function testIsEmailAuthEnabled(string $role, ?string $email, bool $expected): void
    {
        $user = (new User())->setRoles([$role]);
        $signalementUser = new SignalementUser('12345678:occupant', $user, $email);

        $this->assertSame($expected, $signalementUser->isEmailAuthEnabled());
    }
}
