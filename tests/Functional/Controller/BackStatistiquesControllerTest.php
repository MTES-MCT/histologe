<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackStatistiquesControllerTest extends WebTestCase
{
    use SessionHelper;
    private const USER_SUPER_ADMIN = 'admin-01@histologe.fr';
    private const USER_ADMIN_TERRITOIRE = 'admin-territoire-13-01@histologe.fr';
    private const USER_PARTNER = 'user-13-01@histologe.fr';

    public function testStatistiquesFilterRouteNotLogged(): void
    {
        $client = static::createClient();

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques_filter',
            )
        );

        $this->assertResponseRedirects('/connexion');
    }

    public function testStatistiquesRouteAdmin(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_SUPER_ADMIN]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
    }

    public function testStatistiquesFilterRouteAdmin(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_SUPER_ADMIN]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques_filter',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();

        $this->assertEquals(27, $response['count_signalement']);
        $this->assertEquals(66.3, $response['average_criticite']);
        $this->assertEquals(48.1, $response['average_days_validation']);
        $this->assertEquals(15, $response['average_days_closure']);
    }

    public function testStatistiquesRouteRT(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITOIRE]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
    }

    public function testStatistiquesFilterRouteRT(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITOIRE]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques_filter',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();

        $this->assertEquals(17, $response['count_signalement']);
        $this->assertEquals(97.6, $response['average_criticite']);
        $this->assertEquals(60.6, $response['average_days_validation']);
        $this->assertEquals(15, $response['average_days_closure']);
    }

    public function testStatistiquesFilterRouteRTArles(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITOIRE]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques_filter',
                [
                    'communes' => '["Arles"]',
                ]
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();

        $this->assertEquals(17, $response['count_signalement']);
        $this->assertEquals(0, $response['count_signalement_filtered']);
        $this->assertEquals(97.6, $response['average_criticite']);
        $this->assertEquals(0, $response['average_criticite_filtered']);
    }

    public function testStatistiquesRoutePartner(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
    }

    public function testStatistiquesFilterRoutePartner(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques_filter',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();

        $this->assertEquals(3, $response['count_signalement']);
        $this->assertEquals(100, $response['average_criticite']);
        $this->assertEquals(162.3, $response['average_days_validation']);
        $this->assertEquals(0, $response['average_days_closure']);
    }
}
