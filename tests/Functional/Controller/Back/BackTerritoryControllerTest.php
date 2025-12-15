<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackTerritoryControllerTest extends WebTestCase
{
    public function testGetBailleursList(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $client->request('GET', $router->generate('back_territory_bailleurs', ['territory' => $user->getFirstTerritory()->getId()]));
        $this->assertResponseIsSuccessful();

        $client->request('GET', $router->generate('back_territory_bailleurs', ['territory' => $user->getFirstTerritory()->getId() + 1]));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
