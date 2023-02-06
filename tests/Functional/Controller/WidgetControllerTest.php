<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class WidgetControllerTest extends WebTestCase
{
    private const USER_SUPER_ADMIN = 'admin-01@histologe.fr';
    private const USER_ADMIN_TERRITOIRE = 'admin-territoire-13-01@histologe.fr';
    private const USER_PARTNER = 'user-13-01@histologe.fr';

    public function testWidgetRouteDataKpi(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_SUPER_ADMIN]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate(
            'back_dashboard_widget', [
                'widgetType' => WidgetType::WIDGET_TYPE_DATA_KPI,
            ])
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('data-kpi', $response['type']);
        $this->assertArrayHasKey('cardNouveauxSignalements', $response['data']['widgetCards']);
        $this->assertArrayHasKey('cardCloturesPartenaires', $response['data']['widgetCards']);
        $this->assertArrayHasKey('cardCloturesGlobales', $response['data']['widgetCards']);
        $this->assertArrayHasKey('countSignalement', $response['data']);
        $this->assertArrayHasKey('countSuivi', $response['data']);
        $this->assertArrayHasKey('countUser', $response['data']);
    }

    public function testWidgetRouteEsaboraEvenements(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_SUPER_ADMIN]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate(
            'back_dashboard_widget', [
            'widgetType' => WidgetType::WIDGET_TYPE_ESABORA_EVENTS,
        ])
        );
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('esabora-evenements', $response['type']);

        foreach ($response['data'] as $event) {
            $this->assertMatchesRegularExpression('/success|failed/', $event['status']);
            $this->assertArrayHasKey('last_event', $event);
            $this->assertArrayHasKey('nom', $event);
            $this->assertArrayHasKey('reference', $event);
            $this->assertArrayHasKey('status', $event);
            $this->assertArrayHasKey('title', $event);
        }
    }

    public function testWidgetRouteAffectationPartenaires(): void
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
                'back_dashboard_widget',
                ['widgetType' => WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER]
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('affectations-partenaires', $response['type']);
        $this->assertEquals('13', $response['territory']['zip']);
        $this->assertEquals('Bouches-du-Rhône', $response['territory']['name']);

        foreach ($response['data'] as $data) {
            $this->assertArrayHasKey('waiting', $data);
            $this->assertArrayHasKey('refused', $data);
            $this->assertArrayHasKey('zip', $data);
            $this->assertArrayHasKey('nom', $data);
        }
    }

    public function testWidgetRouteSignalementTerritoires(): void
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
                'back_dashboard_widget',
                ['widgetType' => WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE]
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('signalements-territoires', $response['type']);

        foreach ($response['data'] as $data) {
            $this->assertArrayHasKey('zip', $data);
            $this->assertArrayHasKey('territory_name', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('new', $data);
            $this->assertArrayHasKey('no_affected', $data);
        }
    }

    public function testWidgetRouteSignalementAccepteSansSuivi(): void
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
                'back_dashboard_widget',
                ['widgetType' => WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI]
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('signalements-acceptes-sans-suivi', $response['type']);
        $this->assertEquals('13', $response['territory']['zip']);
        $this->assertEquals('Bouches-du-Rhône', $response['territory']['name']);

        foreach ($response['data'] as $data) {
            $this->assertArrayHasKey('count_no_suivi', $data);
            $this->assertArrayHasKey('nom', $data);
        }
    }

    /**
     * @dataProvider provideWidgetRoutesSuperAdmin
     */
    public function testWidgetRouteForSuperAdmin(string $widgetType, int $statusCode): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_SUPER_ADMIN]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_dashboard_widget', ['widgetType' => $widgetType]));

        $this->assertEquals(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    /**
     * @dataProvider provideWidgetRoutesAdminTerritoire
     */
    public function testWidgetRouteForAdminTerritoire(string $widgetType, int $statusCode): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITOIRE]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_dashboard_widget', ['widgetType' => $widgetType]));

        $this->assertEquals(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    /**
     * @dataProvider provideWidgetRoutesUserPartner
     */
    public function testWidgetRouteForUserPartner(string $widgetType, int $statusCode): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_dashboard_widget', ['widgetType' => $widgetType]));

        $this->assertEquals(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function provideWidgetRoutesSuperAdmin(): \Generator
    {
        yield 'data-kpi' => [WidgetType::WIDGET_TYPE_DATA_KPI, 200];
        yield 'affectations-partenaires' => [WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER, 200];
        yield 'signalements-acceptes-sans-suivi' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI, 403];
        yield 'signalements-territoires' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE, 200];
        yield 'esabora-evenements' => [WidgetType::WIDGET_TYPE_ESABORA_EVENTS, 200];
    }

    public function provideWidgetRoutesAdminTerritoire(): \Generator
    {
        yield 'data-kpi' => [WidgetType::WIDGET_TYPE_DATA_KPI, 200];
        yield 'affectations-partenaires' => [WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER, 200];
        yield 'signalements-acceptes-sans-suivi' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI, 200];
        yield 'signalements-territoires' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE, 403];
        yield 'esabora-evenements' => [WidgetType::WIDGET_TYPE_ESABORA_EVENTS, 403];
    }

    public function provideWidgetRoutesUserPartner(): \Generator
    {
        yield 'data-kpi' => [WidgetType::WIDGET_TYPE_DATA_KPI, 200];
        yield 'affectations-partenaires' => [WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER, 403];
        yield 'signalements-acceptes-sans-suivi' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI, 403];
        yield 'signalements-territoires' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE, 403];
        yield 'esabora-evenements' => [WidgetType::WIDGET_TYPE_ESABORA_EVENTS, 403];
    }
}
