<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\SignalementUsagerRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class SignalementCreateControllerTest extends WebTestCase
{
    use SessionHelper;
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private PartnerRepository $partnerRepository;
    private SignalementUsagerRepository $signalementUsagerRepository;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var UserRepository $userRepository */
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->signalementUsagerRepository = static::getContainer()->get(SignalementUsagerRepository::class);
        $this->userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
    }

    public function testCreateWithDoublon(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/creer');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '8 Rue de la tourmentinerie 44850 Saint-Mars-du-Désert',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[natureLogement]' => 'autre',
            'signalement_draft_address[profileDeclarant]' => 'LOCATAIRE',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('hasDuplicates', $response);
        $this->assertTrue($response['hasDuplicates']);
        $this->assertStringContainsString('Voir les signalements', $response['labelBtnDuplicates']);

        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '8 Rue de la tourmentinerie 44850 Saint-Mars-du-Désert',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[profileDeclarant]' => 'LOCATAIRE',
            'signalement_draft_address[forceSave]' => '1',
        ]);

        $this->client->submit($form);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalement/brouillon/editer/', $response['url']);

        $signalements = $this->signalementRepository->findBy([
            'adresseOccupant' => '8 Rue de la tourmentinerie',
            'cpOccupant' => '44850',
            'villeOccupant' => 'Saint-Mars-du-Désert',
        ], ['createdAt' => 'DESC']);
        $this->assertCount(2, $signalements);
        $this->assertEquals($user->getId(), $signalements[0]->getCreatedBy()->getId());
        $this->assertEquals(SignalementStatus::DRAFT, $signalements[0]->getStatut());
        $this->assertEquals(44, $signalements[1]->getTerritory()->getZip());
    }

    public function testCreateWithDoublonDraft(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-44-02@signal-logement.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/creer');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => 'Route des Funeries 44850 Le Cellier',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[natureLogement]' => 'autre',
            'signalement_draft_address[natureLogementAutre]' => 'roulotte',
            'signalement_draft_address[profileDeclarant]' => 'LOCATAIRE',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('hasDuplicates', $response);
        $this->assertTrue($response['hasDuplicates']);
        $this->assertStringContainsString('Voir mes brouillons', $response['labelBtnDuplicates']);
        $this->assertStringContainsString('Vous avez déjà un brouillon de signalement à cette adresse postale', $response['duplicateContent']);

        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => 'Route des Funeries 44850 Le Cellier',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[profileDeclarant]' => 'LOCATAIRE',
            'signalement_draft_address[forceSave]' => '1',
        ]);

        $this->client->submit($form);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalement/brouillon/editer/', $response['url']);

        $signalements = $this->signalementRepository->findBy([
            'adresseOccupant' => 'Route des Funeries',
            'cpOccupant' => '44850',
            'villeOccupant' => 'Le Cellier',
        ], ['createdAt' => 'DESC']);
        $this->assertCount(2, $signalements);
        $this->assertEquals($user->getId(), $signalements[0]->getCreatedBy()->getId());
        $this->assertEquals(SignalementStatus::DRAFT, $signalements[0]->getStatut());
        $this->assertEquals(44, $signalements[1]->getTerritory()->getZip());
    }

    /**
     * @dataProvider provideCanEditSignalementData
     */
    public function testCanEditSignalement(string $userEmail, string $signalementUuid, int $expectedStatusCode): void
    {
        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/bo/signalement/brouillon/editer/'.$signalementUuid);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function provideCanEditSignalementData(): \Generator
    {
        yield 'edit NEED_VALIDATION signalement' => [
            'userEmail' => 'admin-01@signal-logement.fr',
            'signalementUuid' => '00000000-0000-0000-2022-000000000014',
            'expectedStatusCode' => 403,
        ];
        yield 'edit DRAFT signalement created by other user' => [
            'userEmail' => 'admin-territoire-13-01@signal-logement.fr',
            'signalementUuid' => '00000000-0000-0000-2025-000000000002',
            'expectedStatusCode' => 403,
        ];
        yield 'edit DRAFT signalement created by me' => [
            'userEmail' => 'admin-territoire-44-01@signal-logement.fr',
            'signalementUuid' => '00000000-0000-0000-2025-000000000002',
            'expectedStatusCode' => 200,
        ];
    }

    private function getCrawler(): Crawler
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/editer/00000000-0000-0000-2025-000000000002');
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    public function testEditAddress(): void
    {
        $crawler = $this->getCrawler();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '5 Rue Basse 44350 Guérande',
            'signalement_draft_address[isLogementSocial]' => '0',
            'signalement_draft_address[natureLogement]' => 'maison',
            'signalement_draft_address[profileDeclarant]' => 'BAILLEUR_OCCUPANT',
            'signalement_draft_address[nbOccupantsLogement]' => '4',
            'signalement_draft_address[nbEnfantsDansLogement]' => '2',
            'signalement_draft_address[enfantsDansLogementMoinsSixAns]' => 'non',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertEquals('', $response['url']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertFalse($signalement->getIsLogementSocial());
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals(44, $signalement->getTerritory()->getZip());
        $this->assertEquals('5 Rue Basse', $signalement->getAdresseOccupant());
        $this->assertEquals('44350', $signalement->getCpOccupant());
        $this->assertEquals('Guérande', $signalement->getVilleOccupant());
        $this->assertEquals(ProfileDeclarant::BAILLEUR_OCCUPANT, $signalement->getProfileDeclarant());
        $this->assertEquals('maison', $signalement->getNatureLogement());
        $this->assertEquals(4, $signalement->getTypeCompositionLogement()->getCompositionLogementNombrePersonnes());
        $this->assertEquals(2, $signalement->getTypeCompositionLogement()->getCompositionLogementNombreEnfants());
        $this->assertEquals('non', $signalement->getTypeCompositionLogement()->getCompositionLogementEnfants());
    }

    public function testEditAddressOnOtherTerritory(): void
    {
        $crawler = $this->getCrawler();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '5 Rue basse 30360 Vézénobres',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[profileDeclarant]' => 'LOCATAIRE',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('tabContent', $response);
        $this->assertStringContainsString('pas le droit de créer un signalement sur ce territoire.', $response['tabContent']);
    }

    public function testEditLogement(): void
    {
        $crawler = $this->getCrawler();

        $form = $crawler->filter('#bo-form-signalement-logement')->form();
        $form->setValues([
            'signalement_draft_logement[superficie]' => '15',
            'signalement_draft_logement[cuisine]' => 'oui',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertTrue($response['redirect']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals(15, $signalement->getSuperficie());
        $this->assertEquals('oui', $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisine());
    }

    public function testEditSituation(): void
    {
        $crawler = $this->getCrawler();

        $form = $crawler->filter('#bo-form-signalement-situation')->form();
        $form->setValues([
            'signalement_draft_situation[bail]' => 'oui',
            'signalement_draft_situation[dpe]' => 'non',
            'signalement_draft_situation[classeEnergetique]' => 'A',
            'signalement_draft_situation[etatDesLieux]' => 'oui',
            'signalement_draft_situation[allocataire]' => 'oui',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertTrue($response['redirect']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals('oui', $signalement->getTypeCompositionLogement()->getBailDpeBail());
        $this->assertEquals('non', $signalement->getTypeCompositionLogement()->getBailDpeDpe());
        $this->assertEquals('A', $signalement->getTypeCompositionLogement()->getBailDpeClasseEnergetique());
        $this->assertEquals('oui', $signalement->getTypeCompositionLogement()->getBailDpeEtatDesLieux());
        $this->assertEquals('1', $signalement->getIsAllocataire());
    }

    public function testEditCoordonnees(): void
    {
        $crawler = $this->getCrawler();

        $form = $crawler->filter('#bo-form-signalement-coordonnees')->form();
        $form->setValues([
            'signalement_draft_coordonnees[nomOccupant]' => 'Bernard',
            'signalement_draft_coordonnees[prenomOccupant]' => 'Florent',
            'signalement_draft_coordonnees[mailOccupant]' => 'florent.bernard@floodcast.fr',
            'signalement_draft_coordonnees[denominationAgence]' => 'Arnaque & cie',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertTrue($response['redirect']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals('Bernard', $signalement->getNomOccupant());
        $this->assertEquals('Florent', $signalement->getPrenomOccupant());
        $this->assertEquals('florent.bernard@floodcast.fr', $signalement->getMailOccupant());
        $this->assertEquals('Arnaque & cie', $signalement->getDenominationAgence());
    }

    public function testEditCoordoonneesBailleur(): void
    {
        $crawler = $this->getCrawler();

        $form = $crawler->filter('#bo-form-signalement-coordonnees')->form();
        $form->setValues([
            'signalement_draft_coordonnees[denominationProprio]' => 'Habitat 44',
            'signalement_draft_coordonnees[nomProprio]' => '',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertTrue($response['redirect']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals('Habitat 44', $signalement->getDenominationProprio());
        $this->assertNull($signalement->getNomProprio());
        $this->assertNull($signalement->getBailleur());
    }

    public function testEditCoordoonneesBailleurSocial(): void
    {
        $crawler = $this->getCrawler();

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $signalement->setIsLogementSocial(true);
        $this->signalementRepository->save($signalement, true);

        $form = $crawler->filter('#bo-form-signalement-coordonnees')->form();
        $form->setValues([
            'signalement_draft_coordonnees[denominationProprio]' => 'Habitat 44',
            'signalement_draft_coordonnees[nomProprio]' => 'Pignon',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertTrue($response['redirect']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals('Habitat 44', $signalement->getDenominationProprio());
        $this->assertEquals('Pignon', $signalement->getNomProprio());
        $this->assertEquals('Habitat 44', $signalement->getBailleur()->getName());
    }

    public function testValidationSignalementWithAutoAffectationWithRT(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);

        $route = $this->router->generate('back_signalement_draft_form_validation', ['uuid' => $signalement->getUuid()]);
        $this->client->request('POST', $route, [
            'consent_signalement_tiers' => 'on',
            'consent_donnees_sante' => 'on',
            'agents_selection' => [
                'agents' => [$user->getId()],
            ],
            '_token' => $this->generateCsrfToken($this->client, 'form_signalement_validation'),
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['redirect']);
        $this->assertStringEndsWith($this->router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]), $response['url']);

        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertCount(1, $signalement->getSuivis());
        $this->assertCount(1, $signalement->getAffectations());
        $signalementUsager = $this->signalementUsagerRepository->findOneBy(['signalement' => $signalement]);
        $this->assertEquals('becam@yopmail.com', $signalementUsager->getOccupant()?->getEmail());
        $this->assertNull($signalementUsager->getDeclarant());

        $this->assertEmailCount(2);
        $this->assertCount(1, $this->userSignalementSubscriptionRepository->findBy(['signalement' => $signalement]));
    }

    public function testValidationSignalementWithManualAffectationWithRT(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $signalement->setInseeOccupant(null);
        $partner1 = $this->partnerRepository->findOneBy(['nom' => 'SDIS 44']);
        $partner2 = $this->partnerRepository->findOneBy(['nom' => 'Partner Habitat 44']);

        $route = $this->router->generate('back_signalement_draft_form_validation', ['uuid' => $signalement->getUuid()]);
        $this->client->request('POST', $route, [
            'consent_signalement_tiers' => 'on',
            'consent_donnees_sante' => 'on',
            'agents_selection' => [
                'agents' => [$user->getId()],
            ],
            '_token' => $this->generateCsrfToken($this->client, 'form_signalement_validation'),
            'partner-ids' => $partner1->getId().','.$partner2->getId(),
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $signalementUsager = $this->signalementUsagerRepository->findOneBy(['signalement' => $signalement]);
        $this->assertEquals('becam@yopmail.com', $signalementUsager->getOccupant()?->getEmail());
        $this->assertNull($signalementUsager->getDeclarant());
        $this->assertTrue($response['redirect']);
        $this->assertStringEndsWith($this->router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]), $response['url']);

        $this->assertNull($signalement->getInseeOccupant());
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertCount(1, $signalement->getSuivis());
        $this->assertCount(2, $signalement->getAffectations());

        $this->assertEmailCount(4);
        $this->assertCount(1, $this->userSignalementSubscriptionRepository->findBy(['signalement' => $signalement]));
    }

    public function testValidationSignalementWithManualAffectationWithAgent(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-44-02@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000008']);
        $signalement->setInseeOccupant(null);

        $route = $this->router->generate('back_signalement_draft_form_validation', ['uuid' => $signalement->getUuid()]);
        $this->client->request('POST', $route, [
            'consent_signalement_tiers' => 'on',
            'consent_donnees_sante' => 'on',
            '_token' => $this->generateCsrfToken($this->client, 'form_signalement_validation'),
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['redirect']);
        $this->assertStringEndsWith($this->router->generate('back_signalement_drafts'), $response['url']);

        $this->assertNull($signalement->getInseeOccupant());
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertCount(0, $signalement->getSuivis());
        $this->assertCount(0, $signalement->getAffectations());
        $signalementUsager = $this->signalementUsagerRepository->findOneBy(['signalement' => $signalement]);
        $this->assertEquals('maudcolbert@yopmail.com', $signalementUsager->getOccupant()?->getEmail());
        $this->assertNull($signalementUsager->getDeclarant());

        $this->assertEmailCount(0);
    }

    public function testValidationSignalementWithAutoAffectationWithAgentAffected(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-44-02@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000008']);

        $route = $this->router->generate('back_signalement_draft_form_validation', ['uuid' => $signalement->getUuid()]);
        $this->client->request('POST', $route, [
            'consent_signalement_tiers' => 'on',
            'consent_donnees_sante' => 'on',
            '_token' => $this->generateCsrfToken($this->client, 'form_signalement_validation'),
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['redirect']);
        $this->assertStringEndsWith($this->router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]), $response['url']);

        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertCount(1, $signalement->getSuivis());
        $this->assertCount(1, $signalement->getAffectations());
        $signalementUsager = $this->signalementUsagerRepository->findOneBy(['signalement' => $signalement]);
        $this->assertEquals('maudcolbert@yopmail.com', $signalementUsager->getOccupant()?->getEmail());
        $this->assertNull($signalementUsager->getDeclarant());

        $this->assertEmailCount(1);
    }

    public function testValidationSignalementWithAutoAffectationWithAgentNotAffected(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-44-04@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000008']);
        $signalement->setCreatedBy($user);

        $route = $this->router->generate('back_signalement_draft_form_validation', ['uuid' => $signalement->getUuid()]);
        $this->client->request('POST', $route, [
            'consent_signalement_tiers' => 'on',
            'consent_donnees_sante' => 'on',
            '_token' => $this->generateCsrfToken($this->client, 'form_signalement_validation'),
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['redirect']);
        $this->assertStringEndsWith($this->router->generate('back_signalement_drafts'), $response['url']);

        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertCount(1, $signalement->getSuivis());
        $this->assertCount(1, $signalement->getAffectations());
        $signalementUsager = $this->signalementUsagerRepository->findOneBy(['signalement' => $signalement]);
        $this->assertEquals('maudcolbert@yopmail.com', $signalementUsager->getOccupant()?->getEmail());
        $this->assertNull($signalementUsager->getDeclarant());

        $this->assertEmailCount(2);
    }
}
