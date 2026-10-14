<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Entity\ServiceSecoursRoute;
use App\Repository\ServiceSecoursRouteRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ConfigServiceSecoursControllerTest extends WebTestCase
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

        $route = $router->generate('back_config_service_secours_route_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h2#desc-table', '6 services secours trouvés');
    }

    public function testIndexForUnauthorizedUser(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-30@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_config_service_secours_route_index');
        $client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
        $response = $client->getResponse();
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('text/html', $contentType);
        $this->assertStringContainsString('Access Denied. The user doesn\'t have ROLE_ADMIN.', (string) $response->getContent());
    }

    #[DataProvider('provideIndexFilters')]
    public function testIndexFilters(
        ?string $territoryZip,
        ?ServiceSecoursCategory $category,
        string $expectedText,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([
            'email' => 'admin-01@signal-logement.fr',
        ]);
        $client->loginUser($user);

        $filters = [];

        if (null !== $territoryZip) {
            /** @var TerritoryRepository $territoryRepository */
            $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
            $territory = $territoryRepository->findOneBy(['zip' => $territoryZip]);
            self::assertNotNull($territory);

            $filters['territory'] = $territory->getId();
        }

        if (null !== $category) {
            $filters['category'] = $category->value;
        }

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate(
            'back_config_service_secours_route_index',
            $filters,
        );

        $client->request('GET', $route);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2#desc-table', $expectedText);
    }

    public static function provideIndexFilters(): \Generator
    {
        yield 'territoire seul' => [
            '13',
            null,
            '3 services secours trouvés',
        ];

        yield 'catégorie SDIS' => [
            null,
            ServiceSecoursCategory::SDIS,
            '2 services secours trouvés',
        ];

        yield 'catégorie marins-pompiers' => [
            null,
            ServiceSecoursCategory::MARINS_POMPIERS,
            '1 service secours trouvé',
        ];

        yield 'territoire et catégorie avec résultat' => [
            '13',
            ServiceSecoursCategory::MARINS_POMPIERS,
            '1 service secours trouvé',
        ];

        yield 'territoire et catégorie sans résultat' => [
            '01',
            ServiceSecoursCategory::POLICE,
            '0 service secours trouvé',
        ];
    }

    public function testAddWithCategory(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory13 = $territoryRepository->findOneBy(['zip' => '13']);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_service_secours_route_new');

        $csrfToken = $this->generateCsrfToken($client, 'service_secours_route');
        $client->request('POST', $route, [
            'service_secours_route' => [
                'name' => 'Autre Secours 13',
                'territory' => (string) $territory13->getId(),
                'category' => ServiceSecoursCategory::AUTRE->value,
                'email' => 'autre13@example.com',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();

        /** @var ServiceSecoursRouteRepository $serviceSecourRouteRepository */
        $serviceSecourRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $serviceSecoursRoute = $serviceSecourRouteRepository->findOneBy(['name' => 'Autre Secours 13']);
        $this->assertNotNull($serviceSecoursRoute);
        $this->assertSame('Autre Secours 13', $serviceSecoursRoute->getName());
        $this->assertSame('Autre-Secours-13', $serviceSecoursRoute->getSlug());
        $this->assertSame('autre13@example.com', $serviceSecoursRoute->getEmail());
        $this->assertSame(ServiceSecoursCategory::AUTRE, $serviceSecoursRoute->getCategory());
        $this->assertSame($territory13->getId(), $serviceSecoursRoute->getTerritory()->getId());
    }

    public function testAddDuplicateTerritoryAndCategoryFails(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory13 = $territoryRepository->findOneBy(['zip' => '13']);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_service_secours_route_new');

        // Territory 13 already has a SDIS route from fixtures
        $csrfToken = $this->generateCsrfToken($client, 'service_secours_route');
        $client->request('POST', $route, [
            'service_secours_route' => [
                'name' => 'Doublon SDIS 13',
                'territory' => (string) $territory13->getId(),
                'category' => ServiceSecoursCategory::SDIS->value,
                'email' => 'doublon-sdis13@example.com',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertSelectorTextContains('form', 'Une configuration existe déjà pour ce territoire et cette catégorie.');
    }

    public function testAddDifferentCategorySameTerritorySucceeds(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory01 = $territoryRepository->findOneBy(['zip' => '01']);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_service_secours_route_new');

        // Territory 01 does not have POLICE yet
        $csrfToken = $this->generateCsrfToken($client, 'service_secours_route');
        $client->request('POST', $route, [
            'service_secours_route' => [
                'name' => 'Commissariat Police Ain',
                'territory' => (string) $territory01->getId(),
                'category' => ServiceSecoursCategory::POLICE->value,
                'email' => 'police01@example.com',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();

        /** @var ServiceSecoursRouteRepository $serviceSecourRouteRepository */
        $serviceSecourRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $serviceSecoursRoute = $serviceSecourRouteRepository->findOneBy(['name' => 'Commissariat Police Ain']);
        $this->assertNotNull($serviceSecoursRoute);
        $this->assertSame(ServiceSecoursCategory::POLICE, $serviceSecoursRoute->getCategory());
        $this->assertSame($territory01->getId(), $serviceSecoursRoute->getTerritory()->getId());
    }

    public function testEditWithCategory(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var ServiceSecoursRouteRepository $serviceSecourRouteRepository */
        $serviceSecourRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        /** @var ServiceSecoursRoute $serviceSecoursRoute */
        $serviceSecoursRoute = $serviceSecourRouteRepository->findOneBy(['name' => 'Gendarmerie de l\'Ain']);
        $this->assertNotNull($serviceSecoursRoute);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_service_secours_route_edit', ['id' => $serviceSecoursRoute->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'service_secours_route');
        $client->request('POST', $route, [
            'service_secours_route' => [
                'name' => 'Gendarmerie Modifiée Ain',
                'territory' => (string) $serviceSecoursRoute->getTerritory()->getId(),
                'category' => ServiceSecoursCategory::GENDARMERIE->value,
                'email' => 'gendarmerie-modifiee@example.com',
                'phone' => '0474999999',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();

        $updatedRoute = $serviceSecourRouteRepository->find($serviceSecoursRoute->getId());
        $this->assertSame('Gendarmerie Modifiée Ain', $updatedRoute->getName());
        $this->assertSame('gendarmerie-modifiee@example.com', $updatedRoute->getEmail());
        $this->assertSame('0474999999', $updatedRoute->getPhone());
    }

    public function testEditDuplicateTerritoryAndCategoryFails(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        /** @var ServiceSecoursRoute $serviceSecoursRoute */
        $serviceSecoursRoute = $serviceSecoursRouteRepository->findOneBy(['name' => 'Gendarmerie de l\'Ain']);
        $this->assertNotNull($serviceSecoursRoute);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_service_secours_route_edit', ['id' => $serviceSecoursRoute->getId()]);

        // Attempt to change category to SDIS on territory 01, which already has SDIS 01
        $csrfToken = $this->generateCsrfToken($client, 'service_secours_route');
        $client->request('POST', $route, [
            'service_secours_route' => [
                'name' => 'Gendarmerie Conflit Ain',
                'territory' => (string) $serviceSecoursRoute->getTerritory()->getId(),
                'category' => ServiceSecoursCategory::SDIS->value,
                'email' => 'conflit@example.com',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertSelectorTextContains('form', 'Une configuration existe déjà pour ce territoire et cette catégorie.');
    }
}
