<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\MailerSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerSubscriberTest extends TestCase
{
    public function testEmailIsRedirected(): void
    {
        putenv('APP_NAME=staging');
        $_ENV['MAIL_TEST_ENABLE'] = '1';

        $email = (new Email())
            ->from('noreply@example.com')
            ->to('laurent.robert@signal-logement.fr', 'cyril.pouget@signal-logement.fr')
            ->cc('pascal.nouma@signal-logement.fr')
            ->bcc('ibrahima.bakayoko@signal-logement.fr')
            ->subject('SIGNAL-LOGEMENT LOIRE-ATLANTIQUE - [2024-11 - David] Nouveau suivi')
            ->text('Un nouveau suivi de PAUCEK Jody (Administrateurs Signal-logement) est disponible pour le signalement #2024-11.');

        $envelope = new Envelope(
            sender: new Address('noreply@example.com'),
            recipients: $email->getTo()
        );

        $event = new MessageEvent($email, $envelope, 'smtp', false);

        $subscriber = new MailerSubscriber(
            mailTestEnable: true,
            mailTestEmail: 'mailcatcher@signal-logement.fr'
        );

        $subscriber->onMessage($event);

        /** @var Email $modifiedEmail */
        $modifiedEmail = $event->getMessage();

        $this->assertCount(1, $modifiedEmail->getTo());

        $this->assertEquals(
            'mailcatcher+staging@signal-logement.fr',
            $modifiedEmail->getTo()[0]->getAddress()
        );

        $this->assertEmpty($modifiedEmail->getCc());
        $this->assertEmpty($modifiedEmail->getBcc());

        $this->assertStringStartsWith(
            '[TO: laurent.robert@signal-logement.fr, cyril.pouget@signal-logement.fr | CC: pascal.nouma@signal-logement.fr | BCC: ibrahima.bakayoko@signal-logement.fr] ',
            $modifiedEmail->getSubject()
        );

        $_ENV['MAIL_TEST_ENABLE'] = '0';
    }
}
