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
    private const USER_ADMIN_PARTNER_MULTI_TERRITORIES = 'admin-partenaire-multi-ter-13-01@histologe.fr';
    private const USER_USER_PARTNER_MULTI_TERRITORIES = 'user-partenaire-multi-ter-34-30@histologe.fr';

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
            ['result' => 51, 'label' => 'count_signalement'],
            ['result' => 55.9, 'label' => 'average_criticite'],
        ]];
        yield 'Responsable Territoire' => ['back_statistiques_filter', [], self::USER_ADMIN_TERRITOIRE, [
            ['result' => 26, 'label' => 'count_signalement'],
            ['result' => 88.4, 'label' => 'average_criticite'],
        ]];
        yield 'Partner' => ['back_statistiques_filter', [], self::USER_PARTNER, [
            ['result' => 3, 'label' => 'count_signalement'],
            ['result' => 100, 'label' => 'average_criticite'],
        ]];
        yield 'Super Admin - filtered with EPCI CC d\'Erdre et Gesvres' => ['back_statistiques_filter', ['territoire' => 45, 'epcis' => '["2"]'], self::USER_SUPER_ADMIN, [
            ['result' => 4, 'label' => 'count_signalement'],
            ['result' => 3, 'label' => 'count_signalement_filtered'],
            ['result' => 0, 'label' => 'count_signalement_refuses'],
            ['result' => 0, 'label' => 'count_signalement_archives'],
        ]];
        yield 'RT - filtered with commune Arles' => ['back_statistiques_filter', ['communes' => '["Arles"]'], self::USER_ADMIN_TERRITOIRE, [
            ['result' => 26, 'label' => 'count_signalement'],
            ['result' => 88.4, 'label' => 'average_criticite'],
            ['result' => 0, 'label' => 'count_signalement_filtered'],
            ['result' => 0, 'label' => 'average_criticite_filtered'],
        ]];
        yield 'Admin partenaire multi territories' => ['back_statistiques_filter', [], self::USER_ADMIN_PARTNER_MULTI_TERRITORIES, [
            ['result' => 6, 'label' => 'count_signalement'],
            ['result' => 0, 'label' => 'count_signalement_refuses'],
            ['result' => 0, 'label' => 'count_signalement_archives'],
        ]];
        yield 'Admin partenaire multi territories filtered on Ain' => ['back_statistiques_filter', ['territoire' => 1], self::USER_ADMIN_PARTNER_MULTI_TERRITORIES, [
            ['result' => 1, 'label' => 'count_signalement'],
            ['result' => 0, 'label' => 'count_signalement_refuses'],
            ['result' => 0, 'label' => 'count_signalement_archives'],
        ]];
        yield 'User partenaire multi territories' => ['back_statistiques_filter', [], self::USER_USER_PARTNER_MULTI_TERRITORIES, [
            ['result' => 2, 'label' => 'count_signalement'],
            ['result' => 0, 'label' => 'count_signalement_refuses'],
            ['result' => 0, 'label' => 'count_signalement_archives'],
        ]];
        yield 'User partenaire multi territories filtered on Ain' => ['back_statistiques_filter', ['territoire' => 35], self::USER_USER_PARTNER_MULTI_TERRITORIES, [
            ['result' => 1, 'label' => 'count_signalement'],
            ['result' => 0, 'label' => 'count_signalement_refuses'],
            ['result' => 0, 'label' => 'count_signalement_archives'],
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
