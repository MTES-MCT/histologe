<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

readonly class MailerSubscriber implements EventSubscriberInterface
{
    private string $appName;

    public function __construct(
        #[Autowire(env: 'MAIL_TEST_ENABLE')]
        private bool $mailTestEnable,
        #[Autowire(env: 'MAIL_TEST_EMAIL')]
        private string $mailTestEmail,
    ) {
        $this->appName = getenv('APP_NAME');
    }

    public function onMessage(MessageEvent $event): void
    {
        if ('histologe' === $this->appName) {
            return;
        }

        if (!$this->mailTestEnable) {
            return;
        }

        $message = $event->getMessage();
        if (!$message instanceof Email) {
            return;
        }

        $originalTo = array_map(fn (Address $a) => $a->toString(), $message->getTo());
        $originalCc = array_map(fn (Address $a) => $a->toString(), $message->getCc());
        $originalBcc = array_map(fn (Address $a) => $a->toString(), $message->getBcc());

        $headers = $message->getHeaders();
        $headers->remove('To');
        $headers->remove('Cc');
        $headers->remove('Bcc');

        $subject = $message->getSubject();
        $decoratedSubject = sprintf(
            '[TO: %s | CC: %s | BCC: %s] %s',
            implode(', ', $originalTo),
            implode(', ', $originalCc),
            implode(', ', $originalBcc),
            $subject
        );
        $message->subject($decoratedSubject)->to(new Address($this->withAlias($this->mailTestEmail)));
    }

    private function withAlias(string $email): string
    {
        [$localPart, $domain] = explode('@', $email);
        $appName = empty($this->appName) ? 'local' : $this->appName;

        return sprintf('%s+%s@%s', $localPart, $appName, $domain);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }
}
