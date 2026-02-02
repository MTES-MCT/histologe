<?php

namespace App\Tests\Functional\Controller\Back;

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

        $this->assertSelectorTextContains('h2', '2 services secours trouvés');
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
                'name' => 'TI-DU-DUT',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('h2', '3 services secours trouvés');
    }
}
