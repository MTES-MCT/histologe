<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\CreationSource;
use App\Entity\Enum\EtageType;
use App\Entity\ServiceSecoursRoute;
use App\Repository\ServiceSecoursRouteRepository;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class ServiceSecoursControllerTest extends WebTestCase
{
    public function testRoutes(): void
    {
        $client = static::createClient();
        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $routes = $serviceSecoursRouteRepository->findAll();
        $this->assertCount(2, $routes);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        foreach ($routes as $route) {
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
        $client = static::createClient();
        $this->requestStartFlow($client);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
        self::assertSelectorTextContains('label', 'Matricule');
    }

    public function testSubmitToStep2(): void
    {
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $this->submitStep1($client, $crawler);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('label', 'Adresse du logement');
    }

    public function testSubmitToStep3(): void
    {
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $this->submitStep2($client, $crawler, 'oui');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('label', 'Profil de l\'occupant');
    }

    public function testSubmitToStep4(): void
    {
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler, 'oui');
        $this->submitStep3($client, $crawler);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('label', 'Bailleur averti');
    }

    public function testSubmitToStep5(): void
    {
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler, 'non');
        $crawler = $this->submitStep3($client, $crawler);

        $this->submitStep4($client, $crawler);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('label', 'Désordres');
        self::assertSelectorTextContains('button[data-upload-photos-trigger]', 'Ajouter des photos');
    }

    public function testSubmitToStep6(): void
    {
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler, 'non');
        $crawler = $this->submitStep3($client, $crawler);
        $crawler = $this->submitStep4($client, $crawler);

        $this->submitStep5($client, $crawler);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Récapitulatif et validation');
        self::assertSelectorCount(1, 'h2:contains("Vos coordonnées")');
        self::assertSelectorCount(1, 'h2:contains("Infos sur le logement")');
        self::assertSelectorCount(1, 'h2:contains("Occupation du logement")');
        self::assertSelectorCount(1, 'h2:contains("Propriétaire / Syndic")');
        self::assertSelectorCount(1, 'h2:contains("Désordres")');
    }

    public function testSubmitToFinalStep(): void
    {
        $client = static::createClient();
        $crawler = $this->requestStartFlow($client);
        $crawler = $this->submitStep1($client, $crawler);
        $crawler = $this->submitStep2($client, $crawler);
        $crawler = $this->submitStep3($client, $crawler);
        $crawler = $this->submitStep4($client, $crawler);
        $crawler = $this->submitStep5($client, $crawler);

        $this->submitStep6($client, $crawler);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Le signalement a bien été enregistré !');

        self::assertSelectorTextContains('a.fr-btn.fr-icon-download-line', 'Télécharger le PDF');
        self::assertSelectorTextContains('a.fr-btn.fr-btn--secondary', 'Saisir un autre signalement');

        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['creationSource' => CreationSource::FORM_SERVICE_SECOURS]);

        $this->assertSame('CHARTIER Yohan', $signalement->getNomOccupantComplet());
        $this->assertSame('PIERRE CHARTIER', $signalement->getNomDeclarantComplet());
        $this->assertSame('MAT-123', $signalement->getMatriculeDeclarant());
        $this->assertSame('BMPM', $signalement->getOrigineMissionServiceSecours());
        $this->assertSame('Ordre de mission', $signalement->getOrdreMissionServiceSecours());
        $this->assertSame('27/03/2026', $signalement->getDateMissionServiceSecours()->format('d/m/Y'));
        $this->assertSame('44179', $signalement->getInseeOccupant());
        $this->assertSame('44850', $signalement->getCpOccupant());
        $this->assertFalse($signalement->getIsLogementSocial());
        $this->assertSame('Martin', $signalement->getNomProprio());
        $this->assertSame('Syndic Bueno SCI', $signalement->getDenominationSyndic());
        $this->assertSame('Hello world!!!', $signalement->getAutreSituationVulnerabilite());
        $this->assertCount(12, $signalement->getDesordreCriteres());
        $this->assertArrayHasKey('desordres_service_secours_autre_precision', $signalement->getJsonContent());
        $this->assertSame('Lorem ipsum', $signalement->getJsonContent()['desordres_service_secours_autre_precision']);
        $this->assertSame('oui', $signalement->getAutresOccupantsDesordre());
    }

    private function requestStartFlow(KernelBrowser $client): Crawler
    {
        $serviceSecoursRoute = $this->getServiceSecoursRoute();
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
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

    private function submitStep2(KernelBrowser $client, Crawler $crawler, string $isLogementSocial = 'non'): Crawler
    {
        $form = $crawler->selectButton('Suivant')->form([
            'service_secours[step2][adresseOccupant]' => '8 Rue de la tourmentinerie',
            'service_secours[step2][cpOccupant]' => '44850',
            'service_secours[step2][villeOccupant]' => 'Saint-Mars-du-Désert',
            'service_secours[step2][inseeOccupant]' => '44179',
            'service_secours[step2][adresseAutreOccupant]' => 'Bâtiment A',
            'service_secours[step2][isLogementSocial]' => $isLogementSocial,
            'service_secours[step2][natureLogement]' => 'appartement',
            'service_secours[step2][typeEtageLogement]' => EtageType::AUTRE->value,
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
}
