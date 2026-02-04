<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchNotification;
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
    public function testAllNotifications(string $route, string $tokenName, string $tokenId, string $msgFlash): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate($route);

        $client->request('POST', $route, [$tokenName => $this->generateCsrfToken($client, $tokenId)]);
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function provideAllNotificationOptions(): \Generator
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        yield 'Mark as read' => [
            'back_notifications_list_read',
            'csrf_token',
            'mark_as_read_'.$user->getId(),
            'Toutes les notifications ont bien été marquées comme lues.',
        ];
        yield 'Delete' => [
            'back_notifications_list_delete',
            'csrf_token',
            'delete_notifications_'.$user->getId(),
            'Toutes les notifications ont bien été supprimées.',
        ];
    }

    /**
     * @dataProvider provideSelectedNotificationOptions
     */
    public function testSelectedNotifications(string $route, string $tokenId, string $filter, string $msgFlash): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $notificationsId = $this->getSelectedNotifications($user);
        $route = $generatorUrl->generate($route);

        $client->request('POST', $route, [
            'csrf_token' => $this->generateCsrfToken($client, $tokenId),
            'selected_notifications' => $notificationsId,
            'search_params' => $filter,
        ]);
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertEquals([], $response['flashMessages']);
    }

    public function provideSelectedNotificationOptions(): \Generator
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        yield 'Mark as read' => [
            'back_notifications_list_read',
            'mark_as_read_'.$user->getId(),
            'orderType=s.createdAt-ASC',
            'Les notifications sélectionnées ont bien été marquées comme lues.',
        ];
        yield 'Delete' => [
            'back_notifications_list_delete',
            'delete_notifications_'.$user->getId(),
            'orderType=si.villeOccupant-ASC&page=2',
            'Les notifications sélectionnées ont bien été supprimées.',
        ];
    }

    private function getSelectedNotifications(User $user): string
    {
        $notificationRepository = static::getContainer()->get(NotificationRepository::class);
        $searchNotification = new SearchNotification($user);
        $notificationsUser = $notificationRepository->findFilteredPaginated($searchNotification, Notification::MAX_LIST_PAGINATION);
        $notificationsId = [];
        foreach ($notificationsUser as $notification) {
            $notificationsId[] = $notification->getId();
        }

        return implode('', $notificationsId);
    }
}
