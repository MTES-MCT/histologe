<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class WidgetControllerTest extends WebTestCase
{
    private const string USER_SUPER_ADMIN = 'admin-01@signal-logement.fr';
    private const string USER_ADMIN_TERRITOIRE = 'admin-territoire-13-01@signal-logement.fr';
    private const string USER_PARTNER = 'user-13-01@signal-logement.fr';
    private const string USER_MULTI_TER_ADMIN_PARTNER = 'admin-partenaire-multi-ter-13-01@signal-logement.fr';

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

    public function testWidgetRouteDataKpiForMultiTerritoryAdminPartner(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_MULTI_TER_ADMIN_PARTNER]);
        $client->loginUser($user);

        /** @var Router $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $responses = [];
        foreach ($user->getPartnersTerritories() as $territory) {
            $client->request('GET', $router->generate(
                'back_dashboard_widget', [
                    'widgetType' => WidgetType::WIDGET_TYPE_DATA_KPI,
                    'territory' => $territory->getId(),
                ])
            );
            $response = json_decode($client->getResponse()->getContent(), true);
            $this->assertEquals('data-kpi', $response['type']);
            $this->assertCount(1, $response['territories']);

            $responses[] = $response;
        }
        // cards
        $totalCardTousLesSignalements = 0;
        $totalCardNouvellesAffectations = 0;
        $totalCardSignalementsNouveauxNonDecence = 0;
        $totalCardSignalementsEnCoursNonDecence = 0;
        $totalCardNouveauxSuivis = 0;
        $totalCardSansSuivi = 0;
        $totalCardNoSuiviAfter3Relances = 0;
        // counts signalement
        $totalClosedByAtLeastOnePartner = 0;
        $totalClosedAllPartnersRecently = 0;
        $totalNewNDE = 0;
        $totalCurrentNDE = 0;
        $totalAffected = 0;
        $totalTotal = 0;
        $totalNew = 0;
        $totalActive = 0;
        $totalClosed = 0;
        $totalRefused = 0;
        // counts suivi
        $totalPartner = 0;
        $totalUsager = 0;
        $totalSignalementNewSuivi = 0;
        $totalSignalementNoSuivi = 0;
        $totalNoSuiviAfter3Relances = 0;
        // counts user
        $totalActiveUser = 0;
        $totalInactiveUser = 0;
        foreach ($responses as $territoryResponse) {
            // cards
            $totalCardTousLesSignalements += $territoryResponse['data']['widgetCards']['cardTousLesSignalements']['count'];
            $totalCardNouvellesAffectations += $territoryResponse['data']['widgetCards']['cardNouvellesAffectations']['count'];
            $totalCardSignalementsNouveauxNonDecence += $territoryResponse['data']['widgetCards']['cardSignalementsNouveauxNonDecence']['count'];
            $totalCardSignalementsEnCoursNonDecence += $territoryResponse['data']['widgetCards']['cardSignalementsEnCoursNonDecence']['count'];
            $totalCardNouveauxSuivis += $territoryResponse['data']['widgetCards']['cardNouveauxSuivis']['count'];
            $totalCardSansSuivi += $territoryResponse['data']['widgetCards']['cardSansSuivi']['count'];
            $totalCardNoSuiviAfter3Relances += $territoryResponse['data']['widgetCards']['cardNoSuiviAfter3Relances']['count'];
            // counts signalement
            $totalClosedByAtLeastOnePartner += $territoryResponse['data']['countSignalement']['closedByAtLeastOnePartner'];
            $totalClosedAllPartnersRecently += $territoryResponse['data']['countSignalement']['closedAllPartnersRecently'];
            $totalNewNDE += $territoryResponse['data']['countSignalement']['newNDE'];
            $totalCurrentNDE += $territoryResponse['data']['countSignalement']['currentNDE'];
            $totalAffected += $territoryResponse['data']['countSignalement']['affected'];
            $totalTotal += $territoryResponse['data']['countSignalement']['total'];
            $totalNew += $territoryResponse['data']['countSignalement']['new'];
            $totalActive += $territoryResponse['data']['countSignalement']['active'];
            $totalClosed += $territoryResponse['data']['countSignalement']['closed'];
            $totalRefused += $territoryResponse['data']['countSignalement']['refused'];
            // counts suivi
            $totalPartner += $territoryResponse['data']['countSuivi']['partner'];
            $totalUsager += $territoryResponse['data']['countSuivi']['usager'];
            $totalSignalementNewSuivi += $territoryResponse['data']['countSuivi']['signalementNewSuivi'];
            $totalSignalementNoSuivi += $territoryResponse['data']['countSuivi']['signalementNoSuivi'];
            $totalNoSuiviAfter3Relances += $territoryResponse['data']['countSuivi']['noSuiviAfter3Relances'];
            // counts user
            $totalActiveUser += $territoryResponse['data']['countUser']['active'];
            $totalInactiveUser += $territoryResponse['data']['countUser']['inactive'];
        }

        $client->request('GET', $router->generate(
            'back_dashboard_widget', [
                'widgetType' => WidgetType::WIDGET_TYPE_DATA_KPI,
            ])
        );
        $responseAll = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('data-kpi', $responseAll['type']);
        $this->assertCount(2, $responseAll['territories']);

        // cards
        $this->assertEquals($totalCardTousLesSignalements, $responseAll['data']['widgetCards']['cardTousLesSignalements']['count']);
        $this->assertEquals($totalCardNouvellesAffectations, $responseAll['data']['widgetCards']['cardNouvellesAffectations']['count']);
        $this->assertEquals($totalCardSignalementsNouveauxNonDecence, $responseAll['data']['widgetCards']['cardSignalementsNouveauxNonDecence']['count']);
        $this->assertEquals($totalCardSignalementsEnCoursNonDecence, $responseAll['data']['widgetCards']['cardSignalementsEnCoursNonDecence']['count']);
        $this->assertEquals($totalCardNouveauxSuivis, $responseAll['data']['widgetCards']['cardNouveauxSuivis']['count']);
        $this->assertEquals($totalCardSansSuivi, $responseAll['data']['widgetCards']['cardSansSuivi']['count']);
        $this->assertEquals($totalCardNoSuiviAfter3Relances, $responseAll['data']['widgetCards']['cardNoSuiviAfter3Relances']['count']);
        // counts signalement
        $this->assertEquals($totalClosedByAtLeastOnePartner, $responseAll['data']['countSignalement']['closedByAtLeastOnePartner']);
        $this->assertEquals($totalClosedAllPartnersRecently, $responseAll['data']['countSignalement']['closedAllPartnersRecently']);
        $this->assertEquals($totalNewNDE, $responseAll['data']['countSignalement']['newNDE']);
        $this->assertEquals($totalCurrentNDE, $responseAll['data']['countSignalement']['currentNDE']);
        $this->assertEquals($totalAffected, $responseAll['data']['countSignalement']['affected']);
        $this->assertEquals($totalTotal, $responseAll['data']['countSignalement']['total']);
        $this->assertEquals($totalNew, $responseAll['data']['countSignalement']['new']);
        $this->assertEquals($totalActive, $responseAll['data']['countSignalement']['active']);
        $this->assertEquals($totalClosed, $responseAll['data']['countSignalement']['closed']);
        $this->assertEquals($totalRefused, $responseAll['data']['countSignalement']['refused']);
        // counts suivi
        $this->assertEquals($totalPartner, $responseAll['data']['countSuivi']['partner']);
        $this->assertEquals($totalUsager, $responseAll['data']['countSuivi']['usager']);
        $this->assertEquals($totalSignalementNewSuivi, $responseAll['data']['countSuivi']['signalementNewSuivi']);
        $this->assertEquals($totalSignalementNoSuivi, $responseAll['data']['countSuivi']['signalementNoSuivi']);
        $this->assertEquals($totalNoSuiviAfter3Relances, $responseAll['data']['countSuivi']['noSuiviAfter3Relances']);
        // counts user
        $this->assertEquals($totalActiveUser, $responseAll['data']['countUser']['active']);
        $this->assertEquals($totalInactiveUser, $responseAll['data']['countUser']['inactive']);
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
            $this->assertArrayHasKey('action', $event);
        }
    }

    public function testWidgetRouteAffectationPartenaires(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITOIRE]);
        $firstTerritory = $user->getFirstTerritory();
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
        $this->assertEquals('13', $response['territories'][$firstTerritory->getId()]['zip']);
        $this->assertEquals('Bouches-du-Rhône', $response['territories'][$firstTerritory->getId()]['name']);

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
        $firstTerritory = $user->getFirstTerritory();
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
        $this->assertEquals('13', $response['territories'][$firstTerritory->getId()]['zip']);
        $this->assertEquals('Bouches-du-Rhône', $response['territories'][$firstTerritory->getId()]['name']);

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
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
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
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
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
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
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
