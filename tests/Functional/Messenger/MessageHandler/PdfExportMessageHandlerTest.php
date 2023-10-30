<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\Signalement;
use App\Messenger\Message\PdfExportMessage;
use App\Messenger\MessageHandler\PdfExportMessageHandler;
use App\Repository\SignalementRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Part\DataPart;

class PdfExportMessageHandlerTest extends WebTestCase
{
    public function testHandleGeneratePdfMessage()
    {
        self::bootKernel();

        $container = static::getContainer();

        $messageBus = $container->get(MessageBusInterface::class);

        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $message = (new PdfExportMessage())
            ->setSignalementId($signalement->getId())
            ->setUserEmail('test@yopmail.com');

        $messageBus->dispatch($message);

        $transport = $container->get('messenger.transport.async');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $container->get(PdfExportMessageHandler::class);
        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailAttachmentCount($email, 1);
        $this->assertEmailHtmlBodyContains($email, 'un export pdf est disponible pour le signalement');
        $this->assertEmailHtmlBodyContains($email, '#2023-1');
        $this->assertEmailAddressContains($email, 'To', 'test@yopmail.com');
        /** @var DataPart $attachment */
        $attachment = $email->getAttachments()[0];
        $this->assertEquals('2023-1.pdf', $attachment->getFilename());
        $this->assertEquals('%PDF-1.4', substr($attachment->getBody(), 0, 8));
    }
}
