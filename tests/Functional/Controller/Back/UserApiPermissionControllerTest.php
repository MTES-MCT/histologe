<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserApiPermissionRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class UserApiPermissionControllerTest extends WebTestCase
{
    use SessionHelper;

    /**
     * @dataProvider provideParamsUserApiList
     *
     * @param array<mixed> $params
     */
    public function testIndex(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_api_user_index');
        $client->request('GET', $route, $params);
        if ($nb > 1) {
            $this->assertSelectorTextContains('h2', $nb.' utilisateurs trouvés');
        } else {
            $this->assertSelectorTextContains('h2', $nb.' utilisateur trouvé');
        }
    }

    public function provideParamsUserApiList(): \Generator
    {
        yield 'Search without params' => [[], 6];
        yield 'Search with queryUser api-0' => [['queryUser' => 'api-0'], 2];
        yield 'Search with statut INACTIVE' => [['statut' => 'INACTIVE'], 0];
    }

    public function testIndexAccess(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_api_user_index');
        $client->request('GET', $route);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testPermissionCreate(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $userApi = $userRepository->findOneBy(['email' => 'api-reunion-epci@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_permission_create', ['id' => $userApi->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'user_api_permission');
        $client->request('POST', $route, [
            'user_api_permission' => [
                '_token' => $csrfToken,
                'partner' => '',
                'territory' => 1,
                'partnerType' => 'EPCI',
            ],
        ]);
        $this->assertResponseStatusCodeSame(302);
        /** @var UserApiPermissionRepository $userApiPermissionRepository */
        $userApiPermissionRepository = static::getContainer()->get(UserApiPermissionRepository::class);
        $userApiPermissions = $userApiPermissionRepository->findBy(['user' => $userApi]);
        $this->assertEquals(2, count($userApiPermissions));
    }

    public function testPermissionCreateOnBadUser(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_permission_create', ['id' => $user->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'user_api_permission');
        $client->request('POST', $route, [
            'user_api_permission' => [
                '_token' => $csrfToken,
                'partner' => '',
                'territory' => 1,
                'partnerType' => 'EPCI',
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testPermissionCreateDuplication(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $userApi = $userRepository->findOneBy(['email' => 'api-reunion-epci@signal-logement.fr']);
        $permission = $userApi->getUserApiPermissions()->first();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_permission_create', ['id' => $userApi->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'user_api_permission');
        $client->request('POST', $route, [
            'user_api_permission' => [
                '_token' => $csrfToken,
                'partner' => '',
                'territory' => $permission->getTerritory()->getId(),
                'partnerType' => 'EPCI',
            ],
        ]);
        $this->assertStringContainsString('Cette permission API existe déjà pour', $client->getResponse()->getContent());
    }

    public function testPermissionEdit(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $userApi = $userRepository->findOneBy(['email' => 'api-reunion-epci@signal-logement.fr']);
        $permission = $userApi->getUserApiPermissions()->first();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_permission_edit', ['id' => $permission->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'user_api_permission');
        $client->request('POST', $route, [
            'user_api_permission' => [
                '_token' => $csrfToken,
                'partner' => '',
                'territory' => $permission->getTerritory()->getId(),
                'partnerType' => 'CAF_MSA',
            ],
        ]);
        $this->assertResponseStatusCodeSame(302);
        $this->assertEquals('CAF_MSA', $permission->getPartnerType()->name);
    }

    public function testPermissionDelete(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $userApi = $userRepository->findOneBy(['email' => 'api-reunion-epci@signal-logement.fr']);
        $permission = $userApi->getUserApiPermissions()->first();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_permission_delete', ['id' => $permission->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'user_api_permission_delete');
        $client->request('POST', $route, [
            '_token' => $csrfToken,
        ]);
        $this->assertResponseStatusCodeSame(302);
        /** @var UserApiPermissionRepository $userApiPermissionRepository */
        $userApiPermissionRepository = static::getContainer()->get(UserApiPermissionRepository::class);
        $userApiPermissions = $userApiPermissionRepository->findBy(['user' => $userApi]);
        $this->assertEquals(0, count($userApiPermissions));
    }

    public function testAddUser(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_add');

        $csrfToken = $this->generateCsrfToken($client, 'user_api');
        $client->request('POST', $route, [
            'user_api' => [
                '_token' => $csrfToken,
                'email' => 'add-user-api@signal-logement.fr',
            ],
        ]);
        $this->assertResponseStatusCodeSame(302);
        $user = $userRepository->findOneBy(['email' => 'add-user-api@signal-logement.fr']);
        $this->assertNotNull($user);
    }

    public function testAddExistingUser(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_api_user_add');

        $csrfToken = $this->generateCsrfToken($client, 'user_api');
        $client->request('POST', $route, [
            'user_api' => [
                '_token' => $csrfToken,
                'email' => 'api-01@signal-logement.fr ',
            ],
        ]);
        $this->assertStringContainsString('&quot;api-01@signal-logement.fr&quot; existe déja, merci de saisir un nouvel e-mail', $client->getResponse()->getContent());
    }
}
