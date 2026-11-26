<?php

namespace App\Service\Mailer;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

class AuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        if (!$user instanceof User && !$user instanceof SignalementUser) {
            throw new \LogicException(\sprintf('Expected instance of %s or %s, got %s', User::class, SignalementUser::class, $user::class));
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_AUTH_CODE,
                to: $user->getEmailAuthRecipient(),
                user: $user instanceof SignalementUser ? $user->getUser() : $user,
                params: ['isUsager' => $user instanceof SignalementUser],
                signalement: $user instanceof SignalementUser
                    ? $this->signalementRepository->findOneByCodeForPublic($user->getCodeSuivi())
                    : null,
            )
        );
    }
}
