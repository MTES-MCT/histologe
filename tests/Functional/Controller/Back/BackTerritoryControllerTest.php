<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackTerritoryControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideParamsTerritoryList
     */
    public function testTerritoryList(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_territory_index');
        $client->request('GET', $route, $params);

        if ($nb > 1) {
            $this->assertSelectorTextContains('h2', $nb.' territoires');
        } else {
            $this->assertSelectorTextContains('h2', $nb.' territoire');
        }
    }

    public function provideParamsTerritoryList(): iterable
    {
        yield 'Search without params' => [[], 102];
        yield 'Search with queryName 34' => [['queryName' => '34'], 1];
        yield 'Search with isActive false' => [['isActive' => 0], 35];
    }

    public function testgrilleVisite(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $client->request('GET', $router->generate('back_territory_grille_visite', ['territory' => $user->getFirstTerritory()->getId()]));
        $this->assertResponseIsSuccessful();

        $client->request('GET', $router->generate('back_territory_grille_visite', ['territory' => $user->getFirstTerritory()->getId() + 1]));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testgrilleVisiteDisabled(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-01-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $client->request('GET', $router->generate('back_territory_grille_visite', ['territory' => $user->getFirstTerritory()->getId()]));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetBailleursList(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-44-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $client->request('GET', $router->generate('back_territory_bailleurs', ['territory' => $user->getFirstTerritory()->getId()]));
        $this->assertResponseIsSuccessful();

        $client->request('GET', $router->generate('back_territory_bailleurs', ['territory' => $user->getFirstTerritory()->getId() + 1]));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
