<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\User;
use App\Messenger\Message\UserExportMessage;
use App\Messenger\MessageHandler\UserExportMessageHandler;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchUser;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class UserExportMessageHandlerTest extends WebTestCase
{
    public function testHandleGenerateListCsvForSA(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);
        $userEmail = 'admin-01@signal-logement.fr';
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        $searchUser = new SearchUser($user);
        $message = new UserExportMessage($searchUser, 'csv');
        $messageBus->dispatch($message);
        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();

        $this->assertCount(1, $envelopes);
        $handler = $container->get(UserExportMessageHandler::class);
        $handler($message);

        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();

        $this->assertEmailHtmlBodyContains($email, 'export de la liste des utilisateurs');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }

    public function testHandleGenerateListXlsxForRT(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);
        $userEmail = 'admin-territoire-13-01@signal-logement.fr';
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        $searchUser = new SearchUser($user);
        $message = new UserExportMessage($searchUser, 'xlsx');
        $messageBus->dispatch($message);
        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();

        $this->assertCount(1, $envelopes);
        $handler = $container->get(UserExportMessageHandler::class);
        $handler($message);

        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();

        $this->assertEmailHtmlBodyContains($email, 'export de la liste des utilisateurs');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }
}
