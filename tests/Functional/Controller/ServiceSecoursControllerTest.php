<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\CreationSource;
use App\Entity\Enum\EtageType;
use App\Entity\ServiceSecoursRoute;
use App\Exception\ProConnect\ProConnectException;
use App\Repository\ServiceSecoursRouteRepository;
use App\Repository\SignalementRepository;
use App\Service\Gouv\Insee\Response\SiretInformation;
use App\Service\Gouv\Insee\SireneService;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Interconnection\Esabora\EsaboraTokenProvider;
use App\Tests\SessionHelper;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class ServiceSecoursControllerTest extends WebTestCase
{
    use SessionHelper;

    private const string SIRET_SDIS_13 = '13000000000001';
    private const string EMAIL_AGENT = 'agent@test.fr';
    private const string EMAIL_AGENT_SDIS_13 = 'agent@sdis13.fr';
    private const string SDIS_13 = 'SDIS 13';

    public function testHomePage(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('service_secours_home', [
            'domain' => 'localhost',
        ]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Vous êtes un service de secours ?');
        $this->assertSelectorExists('button.fr-connect');
        $this->assertSelectorTextContains('button.fr-connect', 'ProConnect');

        $expectedLoginUrl = $router->generate('service_secours_proconnect_login', [
            'domain' => 'localhost',
        ]);
        $formAction = $crawler->filter('form')->attr('action');
        $this->assertSame($expectedLoginUrl, $formAction);
    }

    public function testProConnectLogin(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('getAuthorizationUrl')
            ->willReturn('https://auth.proconnect.fake/authorize');

        $container->set(ProConnectAuthentication::class, $mockAuth);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $url = $router->generate('service_secours_proconnect_login', [
            'domain' => 'localhost',
        ]);

        $client->request('POST', $url);

        $this->assertResponseRedirects('https://auth.proconnect.fake/authorize');
    }

    public function testProConnectLoginFailsGracefully(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('test_key', 'test_val');
        $session->save();

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('getAuthorizationUrl')
            ->willThrowException(new \RuntimeException('Erreur fake'));

        $container->set(ProConnectAuthentication::class, $mockAuth);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $url = $router->generate('service_secours_proconnect_login', [
            'domain' => 'localhost',
        ]);

        $client->request('POST', $url);

        $expectedRedirectUrl = $router->generate('service_secours_home', [
            'domain' => 'localhost',
        ]);
        $this->assertResponseRedirects($expectedRedirectUrl);
        $client->followRedirect();
        $this->assertSelectorExists('.fr-notice--alert');
        $this->assertSelectorExists('.fr-notice--alert a[href$="/signalement"]');
    }

    public function testProConnectCallbackSuccess(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('test_key', 'test_val');
        $session->save();

        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => self::EMAIL_AGENT_SDIS_13,
            'siret' => self::SIRET_SDIS_13,
            'organizational_unit' => self::SDIS_13,
            'chorusdt:matricule' => 'MAT-789',
        ]);

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('authenticateFromCallback')
            ->willReturn($proConnectUser);
        $container->set(ProConnectAuthentication::class, $mockAuth);

        $mockSirene = $this->createMock(SireneService::class);
        $mockSirene
            ->method('getSiret')
            ->with(self::SIRET_SDIS_13)
            ->willReturn(new SiretInformation(self::SIRET_SDIS_13, '13001'));
        $container->set(SireneService::class, $mockSirene);

        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = $container->get(ServiceSecoursRouteRepository::class);
        $expectedRoute = $serviceSecoursRouteRepository->findOneBy(['name' => self::SDIS_13]);
        $this->assertNotNull($expectedRoute);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $callbackUrl = $router->generate('service_secours_proconnect_login_callback', [
            'domain' => 'localhost',
            'code' => 'fake_code',
            'state' => 'fake_state',
        ]);

        $client->request('GET', $callbackUrl);

        $expectedRedirectUrl = $router->generate('service_secours_index', [
            'domain' => 'localhost',
            'slug' => $expectedRoute->getSlug(),
            'uuid' => $expectedRoute->getUuid(),
        ]);

        $this->assertResponseRedirects($expectedRedirectUrl);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSame('MAT-789', $crawler->filter('#service_secours_step1_matriculeDeclarant')->attr('value'));
    }

    public function testProConnectCallbackFailsOnSireneRateLimitExceeded(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('test_key', 'test_val');
        $session->save();

        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => self::EMAIL_AGENT_SDIS_13,
            'siret' => self::SIRET_SDIS_13,
            'organizational_unit' => self::SDIS_13,
        ]);

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('authenticateFromCallback')
            ->willReturn($proConnectUser);
        $container->set(ProConnectAuthentication::class, $mockAuth);

        $mockSirene = $this->createMock(SireneService::class);
        $mockSirene
            ->method('getSiret')
            ->willThrowException(new \App\Service\Gouv\Insee\Exception\SireneRateLimitExceededException('Rate limit exceeded'));
        $container->set(SireneService::class, $mockSirene);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $callbackUrl = $router->generate('service_secours_proconnect_login_callback', [
            'domain' => 'localhost',
            'code' => 'fake_code',
            'state' => 'fake_state',
        ]);

        $client->request('GET', $callbackUrl);

        $expectedRedirectUrl = $router->generate('service_secours_home', [
            'domain' => 'localhost',
        ]);

        $this->assertResponseRedirects($expectedRedirectUrl);
        $client->followRedirect();
        $this->assertSelectorExists('.fr-notice--info');
        $this->assertSelectorTextContains('.fr-notice--info', 'Le service est temporairement indisponible. Veuillez réessayer de vous connecter plus tard.');
    }

    public function testProConnectCallbackFailsOnResolutionError(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('test_key', 'test_val');
        $session->save();

        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => self::EMAIL_AGENT,
            'siret' => '99999999999999',
            'organizational_unit' => 'SDIS 99',
        ]);

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('authenticateFromCallback')
            ->willReturn($proConnectUser);
        $container->set(ProConnectAuthentication::class, $mockAuth);

        $mockSirene = $this->createMock(SireneService::class);
        $mockSirene
            ->method('getSiret')
            ->willReturn(new SiretInformation('99999999999999', '99999'));
        $container->set(SireneService::class, $mockSirene);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $callbackUrl = $router->generate('service_secours_proconnect_login_callback', [
            'domain' => 'localhost',
            'code' => 'fake_code',
            'state' => 'fake_state',
        ]);

        $client->request('GET', $callbackUrl);

        $expectedRedirectUrl = $router->generate('service_secours_home', [
            'domain' => 'localhost',
        ]);

        $this->assertResponseRedirects($expectedRedirectUrl);
        $client->followRedirect();
        $this->assertSelectorExists('.fr-notice--alert');
        $this->assertSelectorTextContains('.fr-notice--alert', 'Nous n\'avons pas pu identifier le service de secours');
        $this->assertSelectorExists('.fr-notice--alert a[href$="/signalement"]');
    }

    public function testProConnectCallbackFailsOnAuthenticationError(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('test_key', 'test_val');
        $session->save();

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('authenticateFromCallback')
            ->willThrowException(new ProConnectException('Auth failure'));
        $container->set(ProConnectAuthentication::class, $mockAuth);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $callbackUrl = $router->generate('service_secours_proconnect_login_callback', [
            'domain' => 'localhost',
            'code' => 'fake_code',
            'state' => 'fake_state',
        ]);

        $client->request('GET', $callbackUrl);

        $expectedRedirectUrl = $router->generate('service_secours_home', [
            'domain' => 'localhost',
        ]);

        $this->assertResponseRedirects($expectedRedirectUrl);
        $client->followRedirect();
        $this->assertSelectorExists('.fr-notice--alert');
        $this->assertSelectorTextContains('.fr-notice--alert', 'Erreur lors du traitement de l\'authentification ProConnect.');
        $this->assertSelectorExists('.fr-notice--alert a[href$="/signalement"]');
    }

    public function testUnknownRouteReturns404(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', 'http://service-secours.localhost/route-qui-n-existe-pas');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRoutes(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $routes = $serviceSecoursRouteRepository->findAll();
        $this->assertCount(6, $routes);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        foreach ($routes as $route) {
            $user = new \App\Security\User\ServiceSecoursUser(
                userIdentifier: self::EMAIL_AGENT.':'.$route->getUuid(),
                email: self::EMAIL_AGENT,
                serviceSecoursRouteUuid: $route->getUuid()
            );
            $client->loginUser($user, 'service_secours');

            // OK
            $url = $router->generate('service_secours_index', [
                'slug' => $route->getSlug(),
                'uuid' => $route->getUuid(),
                'domain' => 'localhost',
            ]);
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful();

            $manifestUrl = $router->generate('service_secours_webmanifest', [
                'slug' => $route->getSlug(),
                'uuid' => $route->getUuid(),
                'domain' => 'localhost',
            ]);
            $client->request('GET', $manifestUrl);
            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('Content-Type', 'application/manifest+json');
            $content = $client->getResponse()->getContent();
            $this->assertIsString($content);
            $this->assertJson($content);
            // KO
            $url = $router->generate('service_secours_index', [
                'slug' => $route->getSlug().'1',
                'uuid' => $route->getUuid(),
                'domain' => 'localhost',
            ]);
            $client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);

            $url = $router->generate('service_secours_index', [
                'slug' => $route->getSlug(),
                'uuid' => $route->getUuid().'1',
                'domain' => 'localhost',
            ]);
            $client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);
        }
    }

    public function testItDisplaysFirstStep(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->requestStartFlow($client);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorTextContains('label', 'Matricule');
    }

    public function testSubmitToStep2(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $this->submitStep1($client, $crawler);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('label', 'Adresse du logement');
    }

    public function testSubmitToStep3(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $this->submitStep2($client, $crawler, 'oui', EtageType::RDC->value);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('legend', 'Profil de l\'occupant');
    }

    public function testSubmitToStep4(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler, 'oui', EtageType::SOUSSOL->value);
        $this->submitStep3($client, $crawler);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('legend', 'Bailleur averti');
    }

    public function testSubmitToStep5(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler, 'non', EtageType::DERNIER_ETAGE->value);
        $crawler = $this->submitStep3($client, $crawler);

        $this->submitStep4($client, $crawler);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('legend', 'Désordres');
        $this->assertSelectorTextContains('button[data-upload-photos-trigger]', 'Ajouter des photos');
    }

    public function testSubmitToStep6(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler, 'non', EtageType::DERNIER_ETAGE->value);
        $crawler = $this->submitStep3($client, $crawler);
        $crawler = $this->submitStep4($client, $crawler);

        $this->submitStep5($client, $crawler);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Récapitulatif et validation');
        $this->assertSelectorCount(1, 'h2:contains("Vos coordonnées")');
        $this->assertSelectorCount(1, 'h2:contains("Infos sur le logement")');
        $this->assertSelectorCount(1, 'h2:contains("Occupation du logement")');
        $this->assertSelectorCount(1, 'h2:contains("Propriétaire / Syndic")');
        $this->assertSelectorCount(1, 'h2:contains("Désordres")');
    }

    public function testSubmitToFinalStep(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $tokenJsonPath = __DIR__.'/../../../tools/wiremock/src/Resources/Oauth2/token.json';
        $tokenData = json_decode((string) file_get_contents($tokenJsonPath), true);

        $esaboraTokenProviderMock = $this->createMock(EsaboraTokenProvider::class);
        $esaboraTokenProviderMock->method('getToken')->willReturn($tokenData['access_token']);
        static::getContainer()->set(EsaboraTokenProvider::class, $esaboraTokenProviderMock);

        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler);
        $crawler = $this->submitStep3($client, $crawler);
        $crawler = $this->submitStep4($client, $crawler);
        $crawler = $this->submitStep5($client, $crawler);

        $this->submitStep6($client, $crawler);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Le signalement a bien été enregistré !');

        $this->assertSelectorTextContains('a.fr-btn.fr-icon-download-line', 'Télécharger le PDF');
        $this->assertSelectorTextContains('a.fr-btn.fr-btn--secondary', 'Saisir un autre signalement');

        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['creationSource' => CreationSource::FORM_SERVICE_SECOURS]);

        $this->assertSame('CHARTIER Yohan', $signalement->getNomOccupantComplet());
        $this->assertSame('PIERRE CHARTIER', $signalement->getNomDeclarantComplet());
        $this->assertSame('MAT-123', $signalement->getMatriculeDeclarant());
        $this->assertSame('BMPM', $signalement->getOrigineMissionServiceSecours());
        $this->assertSame('Ordre de mission', $signalement->getOrdreMissionServiceSecours());
        $this->assertSame('27/03/2026', $signalement->getDateMissionServiceSecours()->format('d/m/Y'));
        $this->assertSame('44179', $signalement->getAddress()->getCityCode());
        $this->assertSame('44850', $signalement->getAddress()->getPostCode());
        $this->assertFalse($signalement->getIsLogementSocial());
        $this->assertSame('2', $signalement->getEtageOccupant());
        $this->assertSame('Martin', $signalement->getNomProprio());
        $this->assertSame('Syndic Bueno SCI', $signalement->getDenominationSyndic());
        $this->assertSame('Hello world!!!', $signalement->getAutreSituationVulnerabilite());
        $this->assertCount(12, $signalement->getDesordreCriteres());
        $this->assertArrayHasKey('desordres_service_secours_autre_precision', $signalement->getJsonContent());
        $this->assertSame('Lorem ipsum', $signalement->getJsonContent()['desordres_service_secours_autre_precision']);
        $this->assertSame('oui', $signalement->getAutresOccupantsDesordre());
        $this->assertSame('non', $signalement->getTypeCompositionLogement()->getTypeLogementRdc());
        $this->assertSame('non', $signalement->getTypeCompositionLogement()->getTypeLogementDernierEtage());
        $this->assertSame('1', $signalement->getTypeCompositionLogement()->getCompositionLogementNombreEnfants());
        $this->assertSame('non', $signalement->getTypeCompositionLogement()->getCompositionLogementEnfants());
        $this->assertSame(3, $signalement->getNbOccupantsLogement()); // 2 adultes + 1 enfant
    }

    private function requestStartFlow(KernelBrowser $client): Crawler
    {
        $serviceSecoursRoute = $this->getServiceSecoursRoute();
        $user = new \App\Security\User\ServiceSecoursUser(
            userIdentifier: self::EMAIL_AGENT.':'.$serviceSecoursRoute->getUuid(),
            email: self::EMAIL_AGENT,
            serviceSecoursRouteUuid: $serviceSecoursRoute->getUuid()
        );
        $client->loginUser($user, 'service_secours');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('service_secours_index', [
            'slug' => $serviceSecoursRoute->getSlug(),
            'uuid' => $serviceSecoursRoute->getUuid(),
            'domain' => 'localhost',
        ]);

        return $client->request('GET', $url);
    }

    private function getServiceSecoursRoute(): ServiceSecoursRoute
    {
        return static::getContainer()
            ->get(ServiceSecoursRouteRepository::class)
            ->find(1);
    }

    private function submitStep1(KernelBrowser $client, Crawler $crawler): Crawler
    {
        $form = $crawler->selectButton('Suivant')->form([
            'service_secours[step1][matriculeDeclarant]' => 'MAT-123',
            'service_secours[step1][nomDeclarant]' => 'Pierre Chartier',
            'service_secours[step1][dateMission]' => '2026-03-27',
            'service_secours[step1][origineMission]' => 'BMPM',
            'service_secours[step1][ordreMission]' => 'Ordre de mission',
        ]);

        return $client->submit($form);
    }

    private function submitStep2(
        KernelBrowser $client,
        Crawler $crawler,
        string $isLogementSocial = 'non',
        string $typeEtageLogement = EtageType::AUTRE->value,
    ): Crawler {
        $form = $crawler->selectButton('Suivant')->form([
            'service_secours[step2][addressAddress]' => '8 Rue de la tourmentinerie',
            'service_secours[step2][addressPostcode]' => '44850',
            'service_secours[step2][addressCity]' => 'Saint-Mars-du-Désert',
            'service_secours[step2][addressCityCode]' => '44179',
            'service_secours[step2][rnbId]' => 'plop',
            'service_secours[step2][adresseAutreOccupant]' => 'Bâtiment A',
            'service_secours[step2][isLogementSocial]' => $isLogementSocial,
            'service_secours[step2][natureLogement]' => 'appartement',
            'service_secours[step2][typeEtageLogement]' => $typeEtageLogement,
            'service_secours[step2][etageOccupant]' => '2',
            'service_secours[step2][nbPiecesLogement]' => '3',
            'service_secours[step2][superficie]' => '65',
        ]);

        return $client->submit($form);
    }

    private function submitStep3(KernelBrowser $client, Crawler $crawler, string $profilOccupant = 'LOCATAIRE'): Crawler
    {
        $form = $crawler->selectButton('Suivant')->form([
            'service_secours[step3][profilOccupant]' => $profilOccupant,
            'service_secours[step3][nomOccupant]' => 'Chartier',
            'service_secours[step3][prenomOccupant]' => 'Yohan',
            'service_secours[step3][mailOccupant]' => 'yohan.chartier@example.org',
            'service_secours[step3][telOccupant]' => '0612345678',
            'service_secours[step3][nbAdultesDansLogement]' => '2',
            'service_secours[step3][nbEnfantsDansLogement]' => '1',
            'service_secours[step3][isEnfantsMoinsSixAnsDansLogement]' => 'non',
            'service_secours[step3][autreVulnerabilite]' => 'Hello world!!!',
        ]);

        return $client->submit($form);
    }

    private function submitStep4(KernelBrowser $client, Crawler $crawler): Crawler
    {
        $form = $crawler->selectButton('Suivant')->form([
            'service_secours[step4][isBailleurAverti]' => 'oui',
            'service_secours[step4][nomProprio]' => 'Martin',
            'service_secours[step4][prenomProprio]' => 'Paul',
            'service_secours[step4][mailProprio]' => 'paul.martin@example.org',
            'service_secours[step4][telProprio]' => '0611223344',
            'service_secours[step4][denominationSyndic]' => 'Syndic Bueno SCI',
            'service_secours[step4][nomSyndic]' => 'Mme Bueno',
            'service_secours[step4][mailSyndic]' => 'syndic.bueno@example.org',
            'service_secours[step4][telSyndic]' => '0622334455',
            'service_secours[step4][telSyndicSecondaire]' => '0633445566',
        ]);

        return $client->submit($form);
    }

    public function submitStep5(KernelBrowser $client, Crawler $crawler): Crawler
    {
        $form = $crawler->selectButton('Vérifier ma saisie')->form([
            'service_secours[step5][desordres]' => [
                'desordres_service_secours_logement_inadapte',
                'desordres_service_secours_humidite_moisissures',
                'desordres_service_secours_chauffage_dangereux',
                'desordres_service_secours_risque_electrique',
                'desordres_service_secours_salete_accumulation_dechets',
                'desordres_service_secours_mauvais_etat_batiment',
                'desordres_service_secours_absence_confort',
                'desordres_service_secours_securite_personnes',
                'desordres_service_secours_risque_saturnisme',
                'desordres_service_secours_nuisibles',
                'desordres_service_secours_parties_communes_degradees',
                'desordres_service_secours_autre',
            ],
            'service_secours[step5][desordresAutre]' => 'Lorem ipsum',
            'service_secours[step5][autresOccupantsDesordre]' => 'oui',
        ]);

        return $client->submit($form);
    }

    public function submitStep6(KernelBrowser $client, Crawler $crawler): void
    {
        $form = $crawler->selectButton('Valider le signalement')->form();
        $client->submit($form);
    }

    public function testAuthenticatedAgentCanAccessAuthorizedRoute(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $serviceSecoursRoute = $this->getServiceSecoursRoute();
        $user = new \App\Security\User\ServiceSecoursUser(
            userIdentifier: self::EMAIL_AGENT.':'.$serviceSecoursRoute->getUuid(),
            email: self::EMAIL_AGENT,
            serviceSecoursRouteUuid: $serviceSecoursRoute->getUuid()
        );
        $client->loginUser($user, 'service_secours');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('service_secours_index', [
            'slug' => $serviceSecoursRoute->getSlug(),
            'uuid' => $serviceSecoursRoute->getUuid(),
            'domain' => 'localhost',
        ]);

        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a.fr-fi-logout-box-r-line[href*="/logout"]');
        $this->assertSelectorTextContains('a.fr-fi-logout-box-r-line[href*="/logout"]', 'Quitter');
    }

    public function testAccessFormAfterCallbackDoesNotCallSireneAgain(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('test_key', 'test_val');
        $session->save();

        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => self::EMAIL_AGENT_SDIS_13,
            'siret' => self::SIRET_SDIS_13,
            'organizational_unit' => self::SDIS_13,
        ]);

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('authenticateFromCallback')
            ->willReturn($proConnectUser);
        $container->set(ProConnectAuthentication::class, $mockAuth);

        $mockSirene = $this->createMock(SireneService::class);
        $mockSirene
            ->expects($this->once())
            ->method('getSiret')
            ->with(self::SIRET_SDIS_13)
            ->willReturn(new SiretInformation(self::SIRET_SDIS_13, '13001'));
        $container->set(SireneService::class, $mockSirene);

        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = $container->get(ServiceSecoursRouteRepository::class);
        $expectedRoute = $serviceSecoursRouteRepository->findOneBy(['name' => self::SDIS_13]);

        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        $callbackUrl = $router->generate('service_secours_proconnect_login_callback', [
            'domain' => 'localhost',
            'code' => 'fake_code',
            'state' => 'fake_state',
        ]);

        $client->request('GET', $callbackUrl);
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Second GET request on the form directly
        $formUrl = $router->generate('service_secours_index', [
            'domain' => 'localhost',
            'slug' => $expectedRoute->getSlug(),
            'uuid' => $expectedRoute->getUuid(),
        ]);
        $client->request('GET', $formUrl);
        $this->assertResponseIsSuccessful();
    }

    public function testPdfAccessAllowedForAuthorizedAgent(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $route = $serviceSecoursRouteRepository->find(1);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-05']);
        $signalement->setServiceSecours($route);

        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($signalement);
        $em->flush();

        $user = new \App\Security\User\ServiceSecoursUser(
            userIdentifier: self::EMAIL_AGENT.':'.$route->getUuid(),
            email: self::EMAIL_AGENT,
            serviceSecoursRouteUuid: $route->getUuid()
        );
        $client->loginUser($user, 'service_secours');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $pdfUrl = $router->generate('service_secours_pdf', [
            'slug' => $route->getSlug(),
            'uuid' => $route->getUuid(),
            'uuidSignalement' => $signalement->getUuid(),
            'domain' => 'localhost',
        ]);

        /** @var UrlSignerInterface $urlSigner */
        $urlSigner = static::getContainer()->get(UrlSignerInterface::class);
        $signedPdfUrl = $urlSigner->sign('http:'.$pdfUrl, 3600);

        $client->request('GET', $signedPdfUrl);
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/pdf', $client->getResponse()->headers->get('Content-Type'));
    }
}
