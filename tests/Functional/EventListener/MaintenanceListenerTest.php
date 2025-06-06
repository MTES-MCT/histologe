<?php

namespace App\Tests\Functional\EventListener;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MaintenanceListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testMaintenanceForApiRoutes(): void
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('POST', $generatorUrl->generate('api_login'));

        $this->assertSame(503, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Maintenance en cours', $response['message']);
    }

    /**
     * @dataProvider provideRoutes
     *
     * @param array<mixed> $parameters
     */
    public function testMaintenanceRedirect(string $routeName, array $parameters = []): void
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate($routeName, $parameters));
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function provideRoutes(): \Generator
    {
        yield 'Contact' => ['front_contact'];
        yield 'Lock dépot signalement' => ['front_signalement'];
        yield 'Lock demande activation' => ['login_activation'];
        yield 'Lock Mot de passe perdu' => ['login_mdp_perdu'];
        yield 'Lock Mise à jour mot de passe' => ['activate_account', ['uuid' => '123456778', 'token' => '000000000']];
        yield 'Fiche signalement' => ['front_suivi_signalement', ['code' => '123456778']];
    }

    public function testNonMaintenanceRequest(): void
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '0';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testMaintenanceNoRedirectForSuperAdmin(): void
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testMaintenanceRedirectForNoSuperAdmin(): void
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    protected function tearDown(): void
    {
        $_ENV['MAINTENANCE_ENABLE'] = '0';
    }
}
