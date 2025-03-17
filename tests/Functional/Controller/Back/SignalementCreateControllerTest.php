<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SignalementCreateControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var UserRepository $userRepository */
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
    }

    public function testCreateWithDoublon()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/creer');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '8 Rue de la tourmentinerie 44850 Saint-Mars-du-Désert',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[occupationLogement]' => 'bail_en_cours',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('hasDuplicates', $response);
        $this->assertTrue($response['hasDuplicates']);

        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '8 Rue de la tourmentinerie 44850 Saint-Mars-du-Désert',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[occupationLogement]' => 'bail_en_cours',
            'signalement_draft_address[forceSave]' => '1',
        ]);

        $this->client->submit($form);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
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

    /**
     * @dataProvider provideCanEditSignalementData
     */
    public function testCanEditSignalement($userEmail, $signalementUuid, $expectedStatusCode)
    {
        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/bo/signalement/brouillon/editer/'.$signalementUuid);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function provideCanEditSignalementData()
    {
        yield 'edit NEED_VALIDATION signalement' => [
            'userEmail' => 'admin-01@histologe.fr',
            'signalementUuid' => '00000000-0000-0000-2022-000000000014',
            'expectedStatusCode' => 403,
        ];
        yield 'edit DRAFT signalement created by other user' => [
            'userEmail' => 'admin-territoire-13-01@histologe.fr',
            'signalementUuid' => '00000000-0000-0000-2025-000000000002',
            'expectedStatusCode' => 403,
        ];
        yield 'edit DRAFT signalement created by me' => [
            'userEmail' => 'admin-territoire-44-01@histologe.fr',
            'signalementUuid' => '00000000-0000-0000-2025-000000000002',
            'expectedStatusCode' => 200,
        ];
    }

    public function testEditAddress()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@histologe.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/editer/00000000-0000-0000-2025-000000000002');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '5 Rue Basse 44350 Guérande',
            'signalement_draft_address[isLogementSocial]' => '0',
            'signalement_draft_address[occupationLogement]' => 'proprio_occupant',
            'signalement_draft_address[nbOccupantsLogement]' => '4',
            'signalement_draft_address[nbEnfantsDansLogement]' => '2',
            'signalement_draft_address[enfantsDansLogementMoinsSixAns]' => 'non',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
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
        $this->assertEquals(4, $signalement->getTypeCompositionLogement()->getCompositionLogementNombrePersonnes());
        $this->assertEquals(2, $signalement->getTypeCompositionLogement()->getCompositionLogementNombreEnfants());
        $this->assertEquals('non', $signalement->getTypeCompositionLogement()->getCompositionLogementEnfants());
    }

    public function testEditAddressOnOtherTerritory()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@histologe.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/editer/00000000-0000-0000-2025-000000000002');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-adresse')->form();
        $form->setValues([
            'signalement_draft_address[adresseCompleteOccupant]' => '5 Rue basse 30360 Vézénobres',
            'signalement_draft_address[isLogementSocial]' => '1',
            'signalement_draft_address[occupationLogement]' => 'bail_en_cours',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('tabContent', $response);
        $this->assertStringContainsString('pas le droit de créer un signalement sur ce territoire.', $response['tabContent']);
    }

    public function testEditLogement()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@histologe.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/editer/00000000-0000-0000-2025-000000000002');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-logement')->form();
        $form->setValues([
            'signalement_draft_logement[natureLogement]' => 'autre',
            'signalement_draft_logement[natureLogementAutre]' => 'roulotte',
            'signalement_draft_logement[superficie]' => '15',
            'signalement_draft_logement[cuisine]' => 'oui',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertTrue($response['redirect']);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000002']);
        $this->assertEquals(SignalementStatus::DRAFT, $signalement->getStatut());
        $this->assertEquals('autre', $signalement->getNatureLogement());
        $this->assertEquals('roulotte', $signalement->getTypeCompositionLogement()->getTypeLogementNatureAutrePrecision());
        $this->assertEquals(15, $signalement->getSuperficie());
        $this->assertEquals('oui', $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisine());
    }

    public function testEditSituation()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@histologe.fr']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/bo/signalement/brouillon/editer/00000000-0000-0000-2025-000000000002');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#bo-form-signalement-situation')->form();
        $form->setValues([
            'signalement_draft_logement[bail]' => 'oui',
            'signalement_draft_logement[dpe]' => 'non',
            'signalement_draft_logement[classeEnergetique]' => 'A',
            'signalement_draft_logement[etatDesLieux]' => 'oui',
            'signalement_draft_logement[allocataire]' => 'oui',
        ]);
        $this->client->submit($form);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
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
}
