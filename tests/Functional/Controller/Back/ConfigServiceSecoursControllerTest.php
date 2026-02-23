<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\ServiceSecoursRouteRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ConfigServiceSecoursControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testIndex(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_config_service_secours_route_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h2#desc-table', '2 services secours trouvés');
    }

    public function testIndexForUnauthorizedUser(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-30@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_config_service_secours_route_index');
        $client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
        $response = $client->getResponse();
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('text/html', $contentType);
        $this->assertStringContainsString('Access Denied. The user doesn\'t have ROLE_ADMIN.', (string) $response->getContent());
    }

    public function testAdd(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_service_secours_route_new');

        $csrfToken = $this->generateCsrfToken($client, 'service_secours_route');
        $client->request('POST', $route, [
            'service_secours_route' => [
                'name' => 'TI DU DUT',
                'email' => 'ti-du-dut@example.com',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('h2#desc-table', '3 services secours trouvés');

        $serviceSecourRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $serviceSecoursRoute = $serviceSecourRouteRepository->findOneBy(['name' => 'TI DU DUT']);
        $this->assertNotNull($serviceSecoursRoute);
        $this->assertSame('TI DU DUT', $serviceSecoursRoute->getName());
        $this->assertSame('TI-DU-DUT', $serviceSecoursRoute->getSlug());
        $this->assertSame('ti-du-dut@example.com', $serviceSecoursRoute->getEmail());
        $this->assertNull($serviceSecoursRoute->getPhone());
    }
}
