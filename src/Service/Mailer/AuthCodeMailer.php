<?php

namespace App\Service\Mailer;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AuthCodeMailer implements AuthCodeMailerInterface
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();

        $this->mailer->send((new Email())
            ->from('ne-pas-repondre@histologe.beta.gouv.fr')
            ->to($user->getEmailAuthRecipient())
            ->subject('Code de vérification')
            ->text("Votre code de vérification est : $authCode")
        );
    }
}
