<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
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

    private function getSelectedNotifications(User $user): string
    {
        $notificationRepository = static::getContainer()->get(NotificationRepository::class);
        $notificationsUser = $notificationRepository->getNotificationUser($user, 1, []);
        $notificationsId = [];
        foreach ($notificationsUser as $notification) {
            $notificationsId[] = $notification->getId();
        }

        return implode('', $notificationsId);
    }

    public function testMarkAsReadAllNotifications(): void
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

    public function testMarkAsReadSelectedNotifications(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        $notificationsId = $this->getSelectedNotifications($user);

        $route = $generatorUrl->generate('back_notifications_list_read', [
            'csrf_token' => $this->generateCsrfToken($client, 'mark_as_read_'.$user->getId()),
            'selected_notifications' => $notificationsId,
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/notifications');
    }

    public function testDeleteAllNotifications(): void
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

    public function testDeleteSelectedNotifications(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        $notificationsId = $this->getSelectedNotifications($user);

        $route = $generatorUrl->generate('back_notifications_list_delete', [
            'csrf_token' => $this->generateCsrfToken($client, 'delete_all_notifications_'.$user->getId()),
            'selected_notifications' => $notificationsId,
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/notifications');
    }
}
