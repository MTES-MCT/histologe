<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    private const string USER_ADMIN = 'admin-01@signal-logement.fr';
    private const string USER_PARTNER_TERRITORY_13 = 'user-13-01@signal-logement.fr';

    public function testIndexWithDashboard(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $client->loginUser($user);
        $url = $router->generate('back_dashboard');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('div');
        $this->assertSelectorTextContains('title', 'Tableau de bord');
    }

    public function testPartnerUserIsRedirectedWithQueryParams(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $partnerUser = $userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);
        $client->loginUser($partnerUser);

        $url = $router->generate('back_dashboard');
        $client->request('GET', $url);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('mesDossiersMessagesUsagers=1', $client->getResponse()->headers->get('Location'));
        $this->assertStringContainsString('mesDossiersAverifier=1', $client->getResponse()->headers->get('Location'));
        $this->assertStringContainsString('mesDossiersActiviteRecente=1', $client->getResponse()->headers->get('Location'));
    }

    public function testIndexDisplaysSearchDashboardAverifierForm(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $client->loginUser($adminUser);

        $url = $router->generate('back_dashboard');
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form#search-dashboard-averifier-form');
        $this->assertCount(1, $crawler->filter('input[name="queryCommune"]'));
    }

    public function testIndexWithInvalidTerritoryIdDoesNotFail(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $client->loginUser($adminUser);

        $url = $router->generate('back_dashboard', ['territoireId' => 999999]);
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('div');
    }

    public function testIndexDisplaysTabAdmin(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $client->loginUser($adminUser);

        $url = $router->generate('back_dashboard');
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#tabpanel-dernieres-actions');
        $this->assertSelectorExists('#tabpanel-dossiers-nouveaux');
        $this->assertSelectorExists('#tabpanel-dossiers-a-fermer');
        $this->assertSelectorExists('#tabpanel-dossiers-messages-usagers');
        $this->assertSelectorExists('#tabpanel-dossiers-a-verifier');
        $this->assertSelectorExists('#tabpanel-dossiers-activite-recente');
    }

    public function testIndexDisplaysTabPartner(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);
        $client->loginUser($adminUser);

        $url = $router->generate('back_dashboard');
        $client->request('GET', $url);

        $this->assertResponseRedirects();
        $redirectUrl = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('mesDossiersMessagesUsagers=1', $redirectUrl);
        $this->assertStringContainsString('mesDossiersAverifier=1', $redirectUrl);
        $this->assertStringContainsString('mesDossiersActiviteRecente=1', $redirectUrl);

        $crawler = $client->followRedirect();
        $this->assertSelectorExists('#tabpanel-dernieres-actions');
        $this->assertSelectorExists('#tabpanel-dossiers-nouveaux');
        $this->assertSelectorNotExists('#tabpanel-dossiers-a-fermer');
        $this->assertSelectorExists('#tabpanel-dossiers-messages-usagers');
        $this->assertSelectorExists('#tabpanel-dossiers-a-verifier');
        $this->assertSelectorExists('#tabpanel-dossiers-activite-recente');
    }

    public function testIndexDisplayTabPartnerMultiTerritory(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-partenaire-multi-ter-34-30@signal-logement.fr']);
        $client->loginUser($user);

        $url = $router->generate('back_dashboard');
        $client->request('GET', $url);

        $this->assertResponseRedirects();
        $redirectUrl = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('mesDossiersMessagesUsagers=1', $redirectUrl);
        $this->assertStringContainsString('mesDossiersAverifier=1', $redirectUrl);
        $this->assertStringContainsString('mesDossiersActiviteRecente=1', $redirectUrl);

        $client->followRedirect();
        $this->assertSelectorExists('#tabpanel-dernieres-actions');
        $this->assertSelectorExists('#tabpanel-dossiers-nouveaux');
        $this->assertSelectorNotExists('#tabpanel-dossiers-a-fermer');
        $this->assertSelectorExists('#tabpanel-dossiers-messages-usagers');
        $this->assertSelectorExists('#tabpanel-dossiers-a-verifier');
        $this->assertSelectorExists('#tabpanel-dossiers-activite-recente');
    }
}
