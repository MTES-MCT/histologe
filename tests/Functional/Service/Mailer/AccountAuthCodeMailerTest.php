<?php

namespace App\Tests\Functional\Service\Mailer;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccountAuthCodeMailerTest extends KernelTestCase
{
    private const string AGENT_WARNING = 'prévenir votre responsable de territoire';

    public static function provideRoles(): \Generator
    {
        yield 'agent' => [User::ROLE_USER_PARTNER, true];
        yield 'usager' => [User::ROLE_USAGER, false];
    }

    #[DataProvider('provideRoles')]
    public function testAgentWarningOnlyForAgents(string $role, bool $expectWarning): void
    {
        $user = (new User())->setEmail('destinataire@example.com')->setRoles([$role]);
        $user->setEmailAuthCode('123456');

        /** @var NotificationMailerRegistry $registry */
        $registry = static::getContainer()->get(NotificationMailerRegistry::class);
        $registry->send(new NotificationMail(
            type: NotificationMailerType::TYPE_ACCOUNT_AUTH_CODE,
            to: 'destinataire@example.com',
            user: $user,
        ));

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, '123456');
        if ($expectWarning) {
            $this->assertEmailHtmlBodyContains($email, self::AGENT_WARNING);
        } else {
            $this->assertEmailHtmlBodyNotContains($email, self::AGENT_WARNING);
        }
    }
}
