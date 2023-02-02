<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class WidgetControllerTest extends WebTestCase
{
    public function testWidgetRouteDataKpi(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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

    /**
     * @dataProvider provideWidgetRoutesSuperAdmin
     */
    public function testWidgetRouteForSuperAdmin(string $widgetType, int $statusCode): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
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
        $user = $userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
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
        yield 'signalements-accepte-sans-suivi' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI, 403];
        yield 'signalements-territoires' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE, 200];
        yield 'esabora-evenements' => [WidgetType::WIDGET_TYPE_ESABORA_EVENTS, 200];
    }

    public function provideWidgetRoutesAdminTerritoire(): \Generator
    {
        yield 'data-kpi' => [WidgetType::WIDGET_TYPE_DATA_KPI, 200];
        yield 'affectations-partenaires' => [WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER, 200];
        yield 'signalements-accepte-sans-suivi' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI, 200];
        yield 'signalements-territoires' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE, 403];
        yield 'esabora-evenements' => [WidgetType::WIDGET_TYPE_ESABORA_EVENTS, 403];
    }

    public function provideWidgetRoutesUserPartner(): \Generator
    {
        yield 'data-kpi' => [WidgetType::WIDGET_TYPE_DATA_KPI, 200];
        yield 'affectations-partenaires' => [WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER, 403];
        yield 'signalements-accepte-sans-suivi' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI, 403];
        yield 'signalements-territoires' => [WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE, 403];
        yield 'esabora-evenements' => [WidgetType::WIDGET_TYPE_ESABORA_EVENTS, 403];
    }
}
