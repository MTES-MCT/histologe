<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ArreteControllerTest extends WebTestCase
{
    use SessionHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['FEATURE_HISTO_ADDRESS'] = '1';
    }

    public function testIndexForSuperAdmin(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_territory_management_arrete_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h2#desc-table', '19 arrêtés trouvés');
    }

    public function testIndexForRT(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-30@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_config_club_event_index');
        $client->request('GET', $route);

        $route = $router->generate('back_territory_management_arrete_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h2#desc-table', '8 arrêtés trouvés');
    }

    public function testIndexForAgent(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-63-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_territory_management_arrete_index');
        $client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
        $response = $client->getResponse();
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('text/html', $contentType);
        $this->assertStringContainsString('Access Denied. The user doesn\'t have ROLE_ADMIN_TERRITORY.', (string) $response->getContent());
    }

    protected function tearDown(): void
    {
        $_ENV['FEATURE_HISTO_ADDRESS'] = '0';
        parent::tearDown();
    }
}
