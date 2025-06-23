<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\User;
use App\Manager\FileManager;
use App\Messenger\Message\ListExportMessage;
use App\Messenger\MessageHandler\ListExportMessageHandler;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Signalement\Export\SignalementExportLoader;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class ListExportMessageHandlerTest extends WebTestCase
{
    private MessageBusInterface $messageBus;
    private UserRepository $userRepository;
    private TransportInterface $transport;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->transport = static::getContainer()->get('messenger.transport.async_priority_high');
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
    }

    public function testHandleGenerateListCsv(): void
    {
        $userEmail = 'admin-01@signal-logement.fr';
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        $message = (new ListExportMessage())
            ->setUserId($user->getId())
            ->setFormat('csv')
            ->setFilters([])
            ->setSelectedColumns([]);

        $this->messageBus->dispatch($message);
        $envelopes = $this->transport->get();
        $this->assertCount(1, $envelopes);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->once())
            ->method('uploadFromFilename')
            ->willReturn('sample.csv');

        $handler = new ListExportMessageHandler(
            static::getContainer()->get(NotificationMailerRegistry::class),
            static::getContainer()->get(LoggerInterface::class),
            static::getContainer()->get(SignalementExportLoader::class),
            static::getContainer()->get(UserRepository::class),
            static::getContainer()->get(ParameterBagInterface::class),
            $uploadHandlerServiceMock,
            static::getContainer()->get(FileManager::class)
        );

        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'export de la liste des signalements');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }

    public function testHandleGenerateListXlsWithOptions(): void
    {
        $userEmail = 'admin-territoire-13-01@signal-logement.fr';
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        $message = (new ListExportMessage())
            ->setUserId($user->getId())
            ->setFormat('xlsx')
            ->setFilters([])
            ->setSelectedColumns(['INSEE']);

        $this->messageBus->dispatch($message);
        $envelopes = $this->transport->get();
        $this->assertCount(1, $envelopes);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->once())
            ->method('uploadFromFilename')
            ->willReturn('sample.xlsx');

        $handler = new ListExportMessageHandler(
            static::getContainer()->get(NotificationMailerRegistry::class),
            static::getContainer()->get(LoggerInterface::class),
            static::getContainer()->get(SignalementExportLoader::class),
            static::getContainer()->get(UserRepository::class),
            static::getContainer()->get(ParameterBagInterface::class),
            $uploadHandlerServiceMock,
            static::getContainer()->get(FileManager::class)
        );

        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'export de la liste des signalements');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }
}
