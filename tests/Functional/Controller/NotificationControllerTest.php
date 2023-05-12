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

    /**
     * @dataProvider provideAllNotificationOptions
     */
    public function testAllNotifications(string $route, string $tokenName, string $tokenId): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate($route, [
            $tokenName => $this->generateCsrfToken($client, $tokenId),
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/notifications');
    }

    private function provideAllNotificationOptions(): \Generator
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

        yield 'Mark as read' => [
            'back_notifications_list_read',
            'mark_as_read',
            'mark_as_read_'.$user->getId(),
        ];
        yield 'Delete' => [
            'back_notifications_list_delete',
            'delete_all_notifications',
            'delete_all_notifications_'.$user->getId(),
        ];
    }

    /**
     * @dataProvider provideSelectedNotificationOptions
     */
    public function testSelectedNotifications(string $route, string $tokenId): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        $notificationsId = $this->getSelectedNotifications($user);

        $route = $generatorUrl->generate($route, [
            'csrf_token' => $this->generateCsrfToken($client, $tokenId),
            'selected_notifications' => $notificationsId,
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/notifications');
    }

    private function provideSelectedNotificationOptions(): \Generator
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

        yield 'Mark as read' => [
            'back_notifications_list_read',
            'mark_as_read_'.$user->getId(),
        ];
        yield 'Delete' => [
            'back_notifications_list_delete',
            'delete_notifications_'.$user->getId(),
        ];
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
}
