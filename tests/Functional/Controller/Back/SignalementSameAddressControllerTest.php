<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementSameAddressControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testIndex(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_signalement_same_address_index');
        $client->request('GET', $route);

        $this->assertCount(5, $client->getCrawler()->filter('.same-address-item'));
    }

    public function testExport(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['name' => 'Gard']);

        $route = $router->generate('back_signalement_same_address_export', ['territoryId' => $territory->getId()]);
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h1', 'Exporter les 2 dossiers à la même adresse');
    }
}
