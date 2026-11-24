<?php

namespace App\Tests\Unit\Service\Mailer;

use App\Entity\User;
use App\Security\User\SignalementUser;
use App\Service\Mailer\AuthCodeMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\TestCase;

class AuthCodeMailerTest extends TestCase
{
    public function testSendAuthCodeToAgent(): void
    {
        $user = (new User())->setEmail('agent@signal-logement.fr')->setRoles([User::ROLE_USER_PARTNER]);

        $registry = $this->createMock(NotificationMailerRegistry::class);
        $registry->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (NotificationMail $mail): bool => NotificationMailerType::TYPE_ACCOUNT_AUTH_CODE === $mail->getType()
                && 'agent@signal-logement.fr' === $mail->getTo()
                && $user === $mail->getUser()
                && false === $mail->getParams()['isUsager']
            ))
            ->willReturn(true);

        (new AuthCodeMailer($registry))->sendAuthCode($user);
    }

    public function testSendAuthCodeToUsagerUsesSignalementEmailAndWrappedUser(): void
    {
        $user = (new User())->setEmail('compte-usager@example.com')->setRoles([User::ROLE_USAGER]);
        $signalementUser = new SignalementUser('12345678:occupant', $user, 'occupant@example.com');

        $registry = $this->createMock(NotificationMailerRegistry::class);
        $registry->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (NotificationMail $mail): bool => NotificationMailerType::TYPE_ACCOUNT_AUTH_CODE === $mail->getType()
                && 'occupant@example.com' === $mail->getTo()
                && $user === $mail->getUser()
                && true === $mail->getParams()['isUsager']
            ))
            ->willReturn(true);

        (new AuthCodeMailer($registry))->sendAuthCode($signalementUser);
    }
}
