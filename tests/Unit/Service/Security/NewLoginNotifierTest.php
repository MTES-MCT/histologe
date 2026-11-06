<?php

namespace App\Tests\Unit\Service\Security;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Security\NewLoginNotifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class NewLoginNotifierTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideRolesAndFlag(): iterable
    {
        yield 'super admin, flag désactivé' => [User::ROLE_ADMIN, false];
        yield 'responsable territoire, flag désactivé' => [User::ROLE_ADMIN_TERRITORY, false];
        yield 'responsable territoire, flag activé' => [User::ROLE_ADMIN_TERRITORY, true];
    }

    #[DataProvider('provideRolesAndFlag')]
    public function testNoNotificationButFingerprintStoredOnFirstLogin(string $role, bool $notifyAllUsers): void
    {
        $user = $this->createUser($role);

        $notificationMailerRegistry = $this->createMock(NotificationMailerRegistry::class);
        $notificationMailerRegistry->expects($this->never())->method('send');

        $this->createNotifier($notificationMailerRegistry, $notifyAllUsers)
            ->handleLogin($user, $this->createRequest('10.0.0.1', 'Firefox'));

        $this->assertNotNull($user->getLastLoginFingerprint());
        $this->assertSame(64, \strlen($user->getLastLoginFingerprint()));
        $this->assertStringNotContainsString('10.0.0.1', $user->getLastLoginFingerprint());
    }

    public function testNoNotificationWhenSameEnvironment(): void
    {
        $user = $this->createUser(User::ROLE_ADMIN);
        $this->loginOnce($user, '10.0.0.1', 'Firefox');
        $fingerprint = $user->getLastLoginFingerprint();

        $notificationMailerRegistry = $this->createMock(NotificationMailerRegistry::class);
        $notificationMailerRegistry->expects($this->never())->method('send');

        $this->createNotifier($notificationMailerRegistry)
            ->handleLogin($user, $this->createRequest('10.0.0.1', 'Firefox'));

        $this->assertSame($fingerprint, $user->getLastLoginFingerprint());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideChangedEnvironment(): iterable
    {
        yield 'adresse IP différente' => ['10.0.0.2', 'Firefox'];
        yield 'navigateur différent' => ['10.0.0.1', 'Chrome'];
    }

    #[DataProvider('provideChangedEnvironment')]
    public function testNotificationForSuperAdminWhenEnvironmentChanged(string $ipAddress, string $userAgent): void
    {
        $user = $this->createUser(User::ROLE_ADMIN);
        $this->loginOnce($user, '10.0.0.1', 'Firefox');
        $previousFingerprint = $user->getLastLoginFingerprint();

        $notificationMailerRegistry = $this->createMock(NotificationMailerRegistry::class);
        $notificationMailerRegistry
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (NotificationMail $notificationMail) use ($ipAddress, $userAgent) {
                $params = $notificationMail->getParams();

                return NotificationMailerType::TYPE_ACCOUNT_NEW_LOGIN === $notificationMail->getType()
                    && ['user@example.com'] === $notificationMail->getEmails()
                    && $ipAddress === $params['ip_address']
                    && $userAgent === $params['user_agent'];
            }));

        $this->createNotifier($notificationMailerRegistry)
            ->handleLogin($user, $this->createRequest($ipAddress, $userAgent));

        $this->assertNotSame($previousFingerprint, $user->getLastLoginFingerprint());
    }

    public function testNoNotificationForOtherRolesWhenFlagDisabled(): void
    {
        $user = $this->createUser(User::ROLE_ADMIN_TERRITORY);
        $this->loginOnce($user, '10.0.0.1', 'Firefox');
        $previousFingerprint = $user->getLastLoginFingerprint();

        $notificationMailerRegistry = $this->createMock(NotificationMailerRegistry::class);
        $notificationMailerRegistry->expects($this->never())->method('send');

        $this->createNotifier($notificationMailerRegistry)
            ->handleLogin($user, $this->createRequest('10.0.0.2', 'Firefox'));

        $this->assertNotSame($previousFingerprint, $user->getLastLoginFingerprint(), 'l\'empreinte doit être mise à jour même sans notification');
    }

    public function testNotificationForOtherRolesWhenFlagEnabled(): void
    {
        $user = $this->createUser(User::ROLE_ADMIN_TERRITORY);
        $this->loginOnce($user, '10.0.0.1', 'Firefox');

        $notificationMailerRegistry = $this->createMock(NotificationMailerRegistry::class);
        $notificationMailerRegistry->expects($this->once())->method('send');

        $this->createNotifier($notificationMailerRegistry, notifyAllUsers: true)
            ->handleLogin($user, $this->createRequest('10.0.0.2', 'Firefox'));
    }

    private function createNotifier(NotificationMailerRegistry $notificationMailerRegistry, bool $notifyAllUsers = false): NewLoginNotifier
    {
        return new NewLoginNotifier($notificationMailerRegistry, 'secret', $notifyAllUsers);
    }

    private function loginOnce(User $user, string $ipAddress, string $userAgent): void
    {
        $this->createNotifier($this->createStub(NotificationMailerRegistry::class))
            ->handleLogin($user, $this->createRequest($ipAddress, $userAgent));
    }

    private function createUser(string $role): User
    {
        return (new User())
            ->setEmail('user@example.com')
            ->setRoles([$role]);
    }

    private function createRequest(string $ipAddress, string $userAgent): Request
    {
        return Request::create('/connexion', Request::METHOD_POST, server: [
            'REMOTE_ADDR' => $ipAddress,
            'HTTP_USER_AGENT' => $userAgent,
        ]);
    }
}
