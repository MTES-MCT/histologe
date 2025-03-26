<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackUserControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideParamsUserList
     */
    public function testUserList(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_user_index');
        $client->request('GET', $route, $params);

        if ($nb > 1) {
            $this->assertSelectorTextContains('h2', $nb.' utilisateurs');
        } else {
            $this->assertSelectorTextContains('h2', $nb.' utilisateur');
        }
    }

    public function provideParamsUserList(): iterable
    {
        yield 'Search without params' => [[], 64];
        yield 'Search with queryUser admin' => [['queryUser' => 'admin'], 21];
        yield 'Search with territory 13' => [['territory' => 13], 15];
        yield 'Search with territory 13 and partner 6 and 7' => [['territory' => 13, 'partners' => [6, 7]], 2];
        yield 'Search with status 0' => [['statut' => 0], 10];
        yield 'Search with role ROLE_ADMIN' => [['role' => 'ROLE_ADMIN'], 3];
        yield 'Search with role ROLE_ADMIN and territory 13' => [['role' => 'ROLE_ADMIN', 'territory' => 13], 0];
        yield 'Search with territory 13 and partnerType Autre' => [['territory' => 13, 'partnerType' => 'AUTRE'], 13];
        yield 'Search with territory 13 and partnerType Ars' => [['territory' => 13, 'partnerType' => 'ARS'], 1];
    }

    /**
     * @dataProvider provideParamsUserExport
     */
    public function testUserExport(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_user_export');
        $client->request('GET', $route, $params);

        $this->assertSelectorTextContains('h1', 'Exporter la liste des '.$nb.' utilisateurs');
    }

    public function provideParamsUserExport(): iterable
    {
        yield 'Search without params' => [[], 14];
        yield 'Search with queryUser user' => [['queryUser' => 'user'], 10];
        yield 'Search with partner 6 and 7' => [['partners' => [2]], 4];
        yield 'Search with status 1' => [['statut' => 1], 10];
        yield 'Search with role ROLE_USER_PARTNER' => [['role' => 'ROLE_USER_PARTNER'], 10];
        yield 'Search with role ROLE_USER_PARTNER and status 1' => [['role' => 'ROLE_USER_PARTNER', 'statut' => 1], 6];
        yield 'Search with territory 13 and partnerType Autre' => [['territory' => 13, 'partnerType' => 'AUTRE'], 14];
        yield 'Search with partnerType Ars' => [['partnerType' => 'ARS'], 1];
    }

    public function testUserExportSA(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_user_export');
        $client->request('GET', $route, ['territory' => 13]);

        $this->assertSelectorTextContains('h1', 'Exporter la liste des 15 utilisateurs');

        $client->request('GET', $route, ['role' => 'ROLE_API_USER']);

        $this->assertSelectorTextContains('h1', 'Exporter la liste des 2 utilisateurs');
    }

    public function testInactiveAccounts(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-34-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_user_inactive_accounts');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h2', '1 utilisateur trouvé');
    }
}
