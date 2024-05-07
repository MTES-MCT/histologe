<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementListControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    /**
     * @dataProvider provideUserEmail
     */
    public function testListSignalementSuccessfullyOrRedirectWithoutError500(string $email): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function provideUserEmail(): \Generator
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findBy(['statut' => User::STATUS_ACTIVE]);
        /** @var User $user */
        foreach ($users as $user) {
            if ($user->getTerritory()) {
                yield $user->getEmail() => [$user->getEmail()];
            }
        }
    }

    public function testDisplayGitBookDocumentationExternalLink(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $crawler = $client->request('GET', $generatorUrl->generate('back_index'));

        $this->assertSelectorTextContains('.fr-sidemenu ul:nth-of-type(2)', 'Documentation');
        $link = $crawler->selectLink('Documentation')->link();
        $this->assertEquals('https://documentation.histologe.beta.gouv.fr', $link->getUri());
    }

    public function testDisplaySignalementMDLRoleAdminTerritory()
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-territoire-69-mdl@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('#signalements-result', '2023-3');
        $this->assertSelectorTextContains('#signalements-result', '2023-4');
        $this->assertSelectorTextContains('table', '2 signalement(s)');
    }

    public function testDisplaySignalementCORRoleAdminTerritory()
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-territoire-69-cor@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('#signalements-result', '2023-2');
        $this->assertSelectorTextContains('#signalements-result', '2023-5');
        $this->assertSelectorTextContains('table', '2 signalement(s)');
    }

    /**
     * @dataProvider provideLinkFilterDashboard
     */
    public function testWidgetLinkFilterDashboard(string $emailUser, string $filter): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => $emailUser]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index').$filter;
        $client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    private function provideLinkFilterDashboard(): \Generator
    {
        $adminUser = 'admin-01@histologe.fr';
        yield 'SUPER_ADMIN - Nouveaux signalements' => [$adminUser, '?statut='.Signalement::STATUS_NEED_VALIDATION];
        yield 'SUPER_ADMIN - Nouveaux suivis' => [$adminUser, '?nouveau_suivi=1'];
        yield 'SUPER_ADMIN - Sans suivis' => [$adminUser, '?sans_suivi_periode='.Suivi::DEFAULT_PERIOD_INACTIVITY];
        yield 'SUPER_ADMIN - Suggestion de clotures' => [$adminUser, '?relances_usager=NO_SUIVI_AFTER_3_RELANCES'];
        yield 'SUPER_ADMIN - Clotures globales' => [$adminUser, '?statut='.Signalement::STATUS_CLOSED];
        yield 'SUPER_ADMIN - Clotures partenaires' => [$adminUser, '?closed_affectation=ONE_CLOSED'];
        yield 'SUPER_ADMIN - Nouveautés non-décence énergétique' => [$adminUser, '?nde=1&statut=1'];
        yield 'SUPER_ADMIN - Non-décence énergétique en cours' => [$adminUser, '?nde=1&statut=2'];

        $adminTerritoryUser = 'admin-territoire-13-01@histologe.fr';
        yield 'ADMIN_T - Nouveaux signalements' => [$adminTerritoryUser, '?statut='.Signalement::STATUS_NEED_VALIDATION];
        yield 'ADMIN_T - Nouveaux suivis' => [$adminTerritoryUser, '?nouveau_suivi=1'];
        yield 'ADMIN_T - Sans suivis' => [$adminTerritoryUser, '?sans_suivi_periode='.Suivi::DEFAULT_PERIOD_INACTIVITY];
        yield 'ADMIN_T - Suggestion de clotures' => [$adminTerritoryUser, '?relances_usager=NO_SUIVI_AFTER_3_RELANCES'];
        yield 'ADMIN_T - Clotures partenaires' => [$adminTerritoryUser, '?closed_affectation=ONE_CLOSED'];
        yield 'ADMIN_T - Mes affectations' => [$adminTerritoryUser, '?territoire_id=13'];

        $partnerUser = 'user-13-01@histologe.fr';
        yield 'PARTNER - Nouvelles affectations' => [$partnerUser, '?statut=1&territoire_id=13'];
        yield 'PARTNER - Nouveaux suivis' => [$partnerUser, '?nouveau_suivi=1'];
        yield 'PARTNER - Sans suivis' => [$partnerUser, '?sans_suivi_periode='.Suivi::DEFAULT_PERIOD_INACTIVITY];
        yield 'PARTNER - Suggestion de clotures' => [$partnerUser, '?relances_usager=NO_SUIVI_AFTER_3_RELANCES'];
        yield 'PARTNER - Tous les signalements' => [$partnerUser, '?territoire_id=13'];
    }

    /**
     * @dataProvider provideFilterSearch
     */
    public function testSearchSignalementByTerms(string $filter, string|array $terms, string $results)
    {
        if ('bo-filters-relances_usager' === $filter) {
            $kernel = self::createKernel();
            $application = new Application($kernel);
            $command = $application->find('app:ask-feedback-usager');
            $commandTester = new CommandTester($command);
            $commandTester->execute([]);
            $commandTester->assertCommandIsSuccessful();
        }

        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('POST', $route, [
            $filter => $terms,
        ]);

        $this->assertSelectorTextContains('table', $results);
        $this->assertResponseIsSuccessful();
    }

    public function provideFilterSearch(): \Generator
    {
        yield 'Search Terms with Reference' => ['bo-filters-searchterms', '2022-1', '1 signalement(s)'];
        yield 'Search Terms with cp Occupant' => ['bo-filters-searchterms', '13003', '12 signalement(s)'];
        yield 'Search Terms with cp Occupant 13005' => ['bo-filters-searchterms', '13005', '3 signalement(s)'];
        yield 'Search Terms with city Occupant' => ['bo-filters-searchterms', 'Gex', '5 signalement(s)'];
        yield 'Search by Territory' => ['bo-filters-territories', ['1'], '5 signalement(s)'];
        yield 'Search by Partner' => ['bo-filters-partners', ['5'], '2 signalement(s)'];
        yield 'Search by Critere' => ['bo-filters-criteres', ['17'], '25 signalement(s)'];
        yield 'Search by Tags' => ['bo-filters-tags', ['3'], '4 signalement(s)'];
        yield 'Search by Parc public/prive' => ['bo-filters-housetypes', ['1'], '5 signalement(s)'];
        yield 'Search by Relances usagers' => ['bo-filters-relances_usager', ['NO_SUIVI_AFTER_3_RELANCES'], '1 signalement(s)'];
    }

    /**
     * @dataProvider provideUserEmail
     */
    public function testListSignalementAsJson(string $email): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_signalement_list_json');

        $client->request('GET', $route, [], [], ['HTTP_Accept' => 'application/json']);
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('filters', $result);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function provideNewFilterSearch(): \Generator
    {
        yield 'Search Terms with Reference' => [['searchTerms' => '2022-1'], 1];
        yield 'Search Terms with cp Occupant' => [['searchTerms' => '13003'], 12];
        yield 'Search Terms with cp Occupant 13005' => [['searchTerms' => '13005'], 3];
        yield 'Search Terms with city Occupant' => [['searchTerms' => 'Gex'], 5];
        yield 'Search Terms with Nom Occupant' => [['searchTerms' => 'Gex'], 5];
        yield 'Search Terms with Firstname Occupant' => [['searchTerms' => 'Mapaire'], 1];
        yield 'Search Terms with Lastname Occupant' => [['searchTerms' => 'Nawell'], 2];
        yield 'Search Terms with Email Occupant' => [['searchTerms' => 'nawell.mapaire@yopmail.com'], 1];
        yield 'Search by Territory 13' => [['territoires' => ['13']], 25];
        yield 'Search by Territory 01 and 69' => [['territoires' => ['01', '70']], 9];
        yield 'Search by Commune' => [['communes' => ['gex', 'marseille']], 30];
        yield 'Search by Commune code postal' => [['communes' => ['13002']], 1];
        yield 'Search by EPCIS' => [['epcis' => ['244400503']], 1];
        yield 'Search by Partner' => [['partenaires' => ['5']], 2];
        yield 'Search by Etiquettes' => [['etiquettes' => ['3']], 4];
        yield 'Search by Parc public' => [['natureParc' => 'public'], 5];
        yield 'Search by Parc public/prive non renseigné' => [['natureParc' => 'non_renseigne'], 1];
        yield 'Search by Enfant moins de 6ans (non)' => [['enfantsM6' => 'non'], 1];
        yield 'Search by Enfant moins de 6ans (oui)' => [['enfantsM6' => 'oui'], 40];
        yield 'Search by Enfant moins de 6ans (non_renseignee)' => [['enfantsM6' => 'non_renseigne'], 3];
        yield 'Search by Date de depot' => [['dateDepotDebut' => '2023-03-08', 'dateDepotFin' => '2023-03-16'], 2];
        yield 'Search by Prcedure estimé' => [['procedure' => 'rsd'], 3];
        yield 'Search by Partenaires affectés' => [['partenaires' => ['5']], 2];
        yield 'Search by Statut de la visite' => [['visiteStatus' => 'Planifiée'], 5];
        yield 'Search by Type de dernier suivi' => [['typeDernierSuivi' => 'automatique'], 16];
        yield 'Search by Date de dernier suivi' => [['dateDernierSuiviDebut' => '2023-04-01', 'dateDernierSuiviFin' => '2023-04-18'], 3];
        yield 'Search by Statut de l\'affectation' => [['statusAffectation' => 'refuse'], 1];
        yield 'Search by Score criticite' => [['criticiteScoreMin' => 5, 'criticiteScoreMax' => 6], 9];
        yield 'Search by Declarant' => [['typeDeclarant' => 'locataire'], 39];
        yield 'Search by Nature du parc' => [['natureParc' => 'public'], 5];
        yield 'Search by Allocataire CAF' => [['allocataire' => 'caf'], 13];
        yield 'Search by Allocataire MSA' => [['allocataire' => 'msa'], 1];
        yield 'Search by Allocataire Oui (CAF+MSA+1)' => [['allocataire' => 'oui'], 14];
        yield 'Search by Allocataire Non (null+empty)' => [['allocataire' => 'non'], 3];
        yield 'Search by Situation Bail en cours' => [['situation' => 'bail_en_cours'], 3];
        yield 'Search by Situation Prévis de départ' => [['situation' => 'preavis_de_depart'], 1];
        yield 'Search by Situation Attente de relogement' => [['situation' => 'attente_relogement'], 2];
    }

    /**
     * @dataProvider provideNewFilterSearch
     */
    public function testFilterSignalements(array $filter, int $results)
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_signalement_list_json');

        $client->request('GET', $route, $filter, [], ['HTTP_Accept' => 'application/json']);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($results, $result['pagination']['total_items'], json_encode($result['list']));
    }

    /**
     * @dataProvider provideUserEmail
     */
    public function testTotalFilterByUser(string $email): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_signalement_list_json');

        $client->request('GET', $route, [], [], ['HTTP_Accept' => 'application/json']);
        $result = json_decode($client->getResponse()->getContent(), true);

        if (\count($result['list']) > 0) {
            $this->assertGreaterThanOrEqual(1, \count($result['list']));
        }

        $this->assertEquals($result['pagination']['total_items'], \count($result['list']));
    }
}
