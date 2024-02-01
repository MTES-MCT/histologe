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

    public function testMaintenanceRedirect()
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testNonMaintenanceRequest()
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '0';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testMaintenanceNoRedirectForSuperAdmin()
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testMaintenanceRedirectForNoSuperAdmin()
    {
        $client = static::createClient();
        $_ENV['MAINTENANCE_ENABLE'] = '1';
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $client->loginUser($user);

        $client->request('GET', $generatorUrl->generate('front_signalement'));
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    protected function tearDown(): void
    {
        $_ENV['MAINTENANCE_ENABLE'] = '0';
    }
}
