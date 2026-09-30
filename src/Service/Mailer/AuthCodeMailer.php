<?php

namespace App\Service\Mailer;

use App\Entity\User;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

class AuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        if (!$user instanceof User) {
            throw new \LogicException(\sprintf('Expected instance of %s, got %s', User::class, $user::class));
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_AUTH_CODE,
                to: $user->getEmailAuthRecipient(),
                user: $user,
            )
        );
    }
}
