<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\User;
use App\Messenger\Message\InactiveUserExportMessage;
use App\Messenger\MessageHandler\InactiveUserExportMessageHandler;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class InactiveUserExportMessageHandlerTest extends WebTestCase
{
    public function testHandleGenerateListXlsxForRT()
    {
        self::bootKernel();
        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);
        $userEmail = 'admin-territoire-13-01@signal-logement.fr';
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        $message = new InactiveUserExportMessage($user->getId(), 'xlsx');
        $messageBus->dispatch($message);
        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();

        $this->assertCount(1, $envelopes);
        $handler = $container->get(InactiveUserExportMessageHandler::class);
        $handler($message);

        $this->assertEmailCount(1);
        /** @var NotificationEmail $email */
        $email = $this->getMailerMessage();

        $this->assertEmailHtmlBodyContains($email, 'export de la liste des utilisateurs inactifs');
        $this->assertEmailAddressContains($email, 'To', $userEmail);
    }
}
