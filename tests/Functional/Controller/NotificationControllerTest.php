<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationControllerTest extends WebTestCase
{
    use SessionHelper;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testMarkAsReadAllNotification(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_notifications_list_read', [
            'mark_as_read' => $this->generateCsrfToken($client, 'mark_as_read_'.$user->getId()),
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/notifications');
    }

    public function testDeleteAllNotification(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_notifications_list_delete', [
            'delete_all_notifications' => $this->generateCsrfToken($client, 'delete_all_notifications_'.$user->getId()),
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/notifications');
    }
}
