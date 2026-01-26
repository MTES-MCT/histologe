<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class AutoAssignerSimulatorControllerTest extends WebTestCase
{
    public function testTerritoryPageLoadsForAdmin(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $url = $router->generate('back_auto_assigner_simulator_territory', [
            'territory' => 13,
        ]);

        $client->request('GET', $url);

        self::assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', '13 - Bouches-du-Rhône');
    }
}
