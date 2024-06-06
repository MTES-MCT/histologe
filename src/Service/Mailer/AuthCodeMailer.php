<?php

namespace App\Service\Mailer;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AuthCodeMailer implements AuthCodeMailerInterface
{
    private $mailer;
    protected ?string $subject = 'Code de vérification';
    protected ?string $text = 'Votre code de vérification est : %authCode%';

    public function __construct(
        MailerInterface $mailer,
        #[Autowire(env: 'REPLY_TO_EMAIL')]
        private string $fromEmail,
        ) {
        $this->mailer = $mailer;
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();

        $this->mailer->send((new Email())
            ->from($this->fromEmail)
            ->to($user->getEmailAuthRecipient())
            ->subject($this->subject)
            ->text(str_replace('%authCode%', $authCode, $this->text))
        );
    }
}
