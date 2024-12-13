<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackZoneControllerTest extends WebTestCase
{
    use SessionHelper;

    /**
     * @dataProvider provideParamsZoneList
     */
    public function testZoneList(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_zone_index');
        $client->request('GET', $route, $params);

        $this->assertSelectorTextContains('h2', $nb.' zone');
    }

    public function provideParamsZoneList(): iterable
    {
        yield 'Search without params' => [[], 3];
        yield 'Search with queryName agde' => [['queryName' => 'agde'], 1];
        yield 'Search with territory 13' => [['territory' => 13], 0];
    }

    public function testZoneShow(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var ZoneRepository $zoneRepository */
        $zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $zones = $zoneRepository->findAll();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_zone_show', ['zone' => $zones[0]->getId()]);
        $client->request('GET', $route);

        $this->assertSelectorTextContains('.fr-badge', 'Partenaire Zone Agde');
        $this->assertSelectorTextContains('h2', '1 signalement dans la zone');
    }

    public function testZoneEdit(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var ZoneRepository $zoneRepository */
        $zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $zones = $zoneRepository->findAll();
        $zone = $zones[0];

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_zone_edit', ['zone' => $zone->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'zone_type');
        $client->request('POST', $route, [
            '_token' => $csrfToken,
            'name' => 'Zone Test',
            'partners' => [],
        ]);

        $this->assertEquals('Zone Test', $zone->getName());
        $this->assertCount(0, $zone->getPartners());
    }
}
