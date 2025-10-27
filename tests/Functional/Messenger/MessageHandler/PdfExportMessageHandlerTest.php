<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\Signalement;
use App\Messenger\Message\PdfExportMessage;
use App\Messenger\MessageHandler\PdfExportMessageHandler;
use App\Repository\SignalementRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class PdfExportMessageHandlerTest extends WebTestCase
{
    public function testHandleGeneratePdfMessage(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $messageBus = $container->get(MessageBusInterface::class);

        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $message = (new PdfExportMessage())
            ->setSignalementId($signalement->getId())
            ->setUserEmail('test@yopmail.com')
            ->setIsForUsager();

        $messageBus->dispatch($message);

        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $container->get(PdfExportMessageHandler::class);
        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Un export pdf est disponible pour le signalement');
        $this->assertEmailHtmlBodyContains($email, '#2023-1');
        $this->assertEmailAddressContains($email, 'To', 'test@yopmail.com');
    }

    public function testHandleGeneratePdfMessageWithDesordres(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $messageBus = $container->get(MessageBusInterface::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-12']);
        $message = (new PdfExportMessage())
            ->setSignalementId($signalement->getId())
            ->setUserEmail('test@yopmail.com')
            ->setIsForUsager();

        $messageBus->dispatch($message);

        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $container->get(PdfExportMessageHandler::class);
        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Un export pdf est disponible pour le signalement');
        $this->assertEmailHtmlBodyContains($email, '#2024-12');
        $this->assertEmailAddressContains($email, 'To', 'test@yopmail.com');
    }
}
