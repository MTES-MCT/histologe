<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDossierResult;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardTabPanelControllerTest extends WebTestCase
{
    #[DataProvider('provideAuthorizedTabBodyType')]
    public function testAccessGrantedForAuthorizedUsers(string $tabBodyType): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $router = static::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
        $url = $router->generate('back_tab_panel_body', ['tabBodyType' => $tabBodyType]);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('provideUnauthorizedTabBodyType')]
    public function testAccessDeniedForUnauthorizedUsers(string $tabBodyType): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $router = static::getContainer()->get('router');
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-partenaire-30@signal-logement.fr']);
        $client->loginUser($user);
        $url = $router->generate('back_tab_panel_body', ['tabBodyType' => $tabBodyType]);

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403, sprintf('Accès interdit pour la section "%s".', $tabBodyType));
    }

    #[DataProvider('provideServiceSecoursAccess')]
    public function testAccessServiceSecoursTabDependsOnTerritoryRoutes(string $email, int $expectedStatusCode): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $router = static::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $url = $router->generate('back_tab_panel_body', [
            'tabBodyType' => TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_SERVICE_SECOURS,
        ]);

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    #[DataProvider('provideTerritoryFilterByUserType')]
    public function testTerritoryFilterIsRenderedForUserType(string $email, bool $selectTerritory, bool $expectedTerritoryFilter): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);

        $territories = $user->getPartnersTerritories();
        $territory = current($territories);
        if (!$territory) {
            $territoryId = 13;
        } else {
            $territoryId = $territory->getId();
        }
        $tabDataManager = $this->createMock(TabDataManager::class);
        $tabDataManager->method('getDossiersActiviteRecente')->willReturn(new TabDossierResult([], 11));
        $container->set(TabDataManager::class, $tabDataManager);

        $router = $container->get('router');
        $routeParameters = ['tabBodyType' => TabBodyType::TAB_DATA_TYPE_DOSSIERS_ACTIVITE_RECENTE];
        if ($selectTerritory) {
            $routeParameters['territoireId'] = $territoryId;
        }
        $url = $router->generate('back_tab_panel_body', $routeParameters);
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $href = $crawler->filter('a.fr-text-label--blue-france')->attr('href');
        parse_str((string) parse_url($href, \PHP_URL_QUERY), $filters);

        if ($expectedTerritoryFilter) {
            $this->assertSame((string) $territoryId, $filters['territoire'] ?? null);
        } else {
            $this->assertArrayNotHasKey('territoire', $filters);
        }
    }

    public static function provideTerritoryFilterByUserType(): \Generator
    {
        yield 'SA without territory' => ['admin-01@signal-logement.fr', false, false];
        yield 'SA with territory' => ['admin-01@signal-logement.fr', true, true];
        yield 'RT' => ['admin-territoire-13-01@signal-logement.fr', false, false];
        yield 'Agent' => ['admin-partenaire-13-01@signal-logement.fr', false, false];
        yield 'Multi-territory without territory' => ['admin-partenaire-multi-ter-13-01@signal-logement.fr', false, false];
        yield 'Multi-territory with territory' => ['admin-partenaire-multi-ter-13-01@signal-logement.fr', true, true];
    }

    public static function provideServiceSecoursAccess(): \Generator
    {
        yield 'RT with service secours routes on territory' => ['admin-territoire-13-01@signal-logement.fr', 200];
        yield 'RT without service secours route on territory' => ['admin-territoire-44-01@signal-logement.fr', 403];
        yield 'Super admin (not allowed on this panel)' => ['admin-01@signal-logement.fr', 403];
    }

    public static function provideAuthorizedTabBodyType(): \Generator
    {
        yield 'with derniers-dossiers' => [TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS];
        yield 'with dossiers-form-pro' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO];
        yield 'with dossiers-form-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER];
        yield 'with dossiers-non-affectation' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION];
        yield 'with dossiers-ferme-partenaire-tous' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS];
        yield 'with dossiers-demandes-fermeture-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER];
        yield 'with dossiers-relance-usager-sans-reponse' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE];
        yield 'with dossiers-messages-nouveaux' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_NOUVEAUX];
        yield 'with dossiers-messages-apres-fermeture' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE];
        yield 'with dossiers-messages-usagers-sans-reponse' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE];
        yield 'with dossiers-sans-activite-partenaire' => [TabBodyType::TAB_DATA_TYPE_SANS_ACTIVITE_PARTENAIRE];
    }

    public static function provideUnauthorizedTabBodyType(): \Generator
    {
        yield 'with dossiers-form-pro' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO];
        yield 'with dossiers-form-service-secours' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_SERVICE_SECOURS];
        yield 'with dossiers-form-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER];
        yield 'with dossiers-ferme-partenaire-tous' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS];
        yield 'with dossiers-demandes-fermeture-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER];
        yield 'with dossiers-relance-usager-sans-reponse' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE];
        yield 'with dossiers-messages-apres-fermeture' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE];
    }
}
