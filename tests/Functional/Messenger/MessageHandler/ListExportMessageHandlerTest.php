<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\User;
use App\Messenger\Message\ListExportMessage;
use App\Messenger\MessageHandler\ListExportMessageHandler;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class ListExportMessageHandlerTest extends WebTestCase
{
    public function testHandleGenerateListCsv()
    {
        self::bootKernel();

        $container = static::getContainer();

        $messageBus = $container->get(MessageBusInterface::class);

        $userEmail = 'admin-01@histologe.fr';

        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        $message = (new ListExportMessage())
            ->setUserId($user->getId())
            ->setFormat('csv')
            ->setFilters([])
            ->setSelectedColumns([]);

        $messageBus->dispatch($message);

        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $container->get(ListExportMessageHandler::class);
        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'export de la liste des signalements');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }

    public function testHandleGenerateListXlsWithOptions()
    {
        self::bootKernel();

        $container = static::getContainer();

        $messageBus = $container->get(MessageBusInterface::class);

        $userEmail = 'admin-territoire-13-01@histologe.fr';

        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        $message = (new ListExportMessage())
            ->setUserId($user->getId())
            ->setFormat('xlsx')
            ->setFilters([])
            ->setSelectedColumns(['INSEE']);

        $messageBus->dispatch($message);

        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $container->get(ListExportMessageHandler::class);
        $handler($message);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'export de la liste des signalements');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }
}
