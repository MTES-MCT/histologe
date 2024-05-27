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

    public function provideRoutesHomepage(): \Generator
    {
        yield 'Super Admin' => ['back_statistiques', self::USER_SUPER_ADMIN];
        yield 'Responsable Territoire' => ['back_statistiques', self::USER_ADMIN_TERRITOIRE];
        yield 'Partner' => ['back_statistiques', self::USER_PARTNER];
    }

    /**
     * @dataProvider provideRoutesHomepage
     */
    public function testStatistiquesHomepage(string $route, string $email): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                $route,
            )
        );

        json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
    }

    public function provideRoutesStatistiquesDatas(): \Generator
    {
        yield 'Super Admin' => ['back_statistiques_filter', [], self::USER_SUPER_ADMIN, [
            ['result' => 41, 'label' => 'count_signalement'],
            ['result' => 61.8, 'label' => 'average_criticite'],
        ]];
        yield 'Responsable Territoire' => ['back_statistiques_filter', [], self::USER_ADMIN_TERRITOIRE, [
            ['result' => 25, 'label' => 'count_signalement'],
            ['result' => 90.6, 'label' => 'average_criticite'],
        ]];
        yield 'Partner' => ['back_statistiques_filter', [], self::USER_PARTNER, [
            ['result' => 3, 'label' => 'count_signalement'],
            ['result' => 100, 'label' => 'average_criticite'],
        ]];
        yield 'RT - filtered with commune Arles' => ['back_statistiques_filter', ['communes' => '["Arles"]'], self::USER_ADMIN_TERRITOIRE, [
            ['result' => 25, 'label' => 'count_signalement'],
            ['result' => 90.6, 'label' => 'average_criticite'],
            ['result' => 0, 'label' => 'count_signalement_filtered'],
            ['result' => 0, 'label' => 'average_criticite_filtered'],
        ]];
    }

    /**
     * @dataProvider provideRoutesStatistiquesDatas
     */
    public function testStatistiquesDatas(string $route, array $params, string $email, array $expectedResponses): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                $route,
                $params
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();

        foreach ($expectedResponses as $expectedResponse) {
            $this->assertEquals($expectedResponse['result'], $response[$expectedResponse['label']]);
        }
    }

    public function testStatistiquesFilterRouteNotLogged(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'back_statistiques_filter',
            )
        );

        $this->assertResponseRedirects('/connexion');
    }
}
