<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Service\Gouv\Rnb\Response\RnbBuilding;
use App\Service\Gouv\Rnb\RnbService;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementActionControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private SuiviRepository $suiviRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->suiviRepository = static::getContainer()->get(SuiviRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testValidationResponseAcceptSignalementSuccess(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000016']);
        $route = $this->router->generate('back_signalement_validation_response', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                'signalement-validation-response' => [
                    'accept' => '1',
                ],
                '_token' => $this->generateCsrfToken($this->client, 'signalement_validation_response_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--success p', 'Statut du signalement mis à jour avec succès !');

        $nbSuiviActive = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::SIGNALEMENT_IS_ACTIVE, 'signalement' => $signalement]);
        $this->assertEquals(1, $nbSuiviActive);
    }

    public function testValidationResponseRefusSignalementSuccess(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000016']);
        $route = $this->router->generate('back_signalement_validation_response', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                'signalement-validation-response' => [
                    'motifRefus' => 'DOUBLON',
                    'suivi' => 'le signalement existe déja sous la référence 123-126',
                ],
                '_token' => $this->generateCsrfToken($this->client, 'signalement_validation_response_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--success p', 'Statut du signalement mis à jour avec succès !');
    }

    public function testValidationResponseSignalementError(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000016']);
        $route = $this->router->generate('back_signalement_validation_response_deny', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                'signalement-validation-response' => [
                    'motifRefus' => 'DOUBLON',
                    'suivi' => 'test',
                ],
                '_token' => $this->generateCsrfToken($this->client, 'signalement_validation_response_'.$signalement->getId()),
            ]
        );
        $csrfToken = $this->generateCsrfToken($this->client, 'refus_signalement');
        $this->client->request('POST', $route, [
            'refus_signalement' => [
                'motifRefus' => 'DOUBLON',
                'description' => 'test',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertStringContainsString('Le message doit contenir au moins 10 caract\u00e8res.', $this->client->getResponse()->getContent());
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testAddSuiviSignalementSuccess(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);
        $route = $this->router->generate('back_signalement_add_suivi', ['uuid' => $signalement->getUuid()]);
        $csrfToken = $this->generateCsrfToken($this->client, 'add_suivi');

        $filesIds = $signalement->getFiles()->filter(function (File $file) {
            return $file->isTypeDocument() && !$file->getIsSuspicious();
        })->map(fn ($file) => $file->getId())->toArray();

        $this->client->request('POST', $route, [
            'add_suivi' => [
                'isPublic' => '1',
                'description' => 'La procédure avance bien, nous vous tiendrons informé de la suite, bon courage !',
                'files' => $filesIds,
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertResponseStatusCodeSame(200);

        $lastSuiviPublic = $this->suiviRepository->findLastPublicSuivi($signalement);
        $this->assertStringContainsString('La procédure avance bien, nous vous tiendrons informé de la suite, bon courage !', $lastSuiviPublic->getDescription());
        $this->assertEquals(3, $lastSuiviPublic->getSuiviFiles()->count());

        $this->assertStringContainsString('<i>3 Fichiers joints :</i>', $lastSuiviPublic->getDescription());
        $this->assertEquals(3, substr_count($lastSuiviPublic->getDescription(), '<a '));

        foreach ($lastSuiviPublic->getSuiviFiles() as $suiviFile) {
            $suiviFile->setFile(null);
            break;
        }
        $this->assertEquals(2, substr_count($lastSuiviPublic->getDescription(), '<a '));
        $this->assertStringContainsString('Fichier supprimé', $lastSuiviPublic->getDescription());
    }

    public function testAddSuiviSignalementErrorInvalidFiles(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);
        $route = $this->router->generate('back_signalement_add_suivi', ['uuid' => $signalement->getUuid()]);
        $csrfToken = $this->generateCsrfToken($this->client, 'add_suivi');
        $this->client->request('POST', $route, [
            'add_suivi' => [
                'isPublic' => '1',
                'description' => 'La procédure avance bien, nous vous tiendrons informé de la suite, bon courage !',
                'files' => [1, 2],
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('Le choix s\u00e9lectionn\u00e9 est invalide.', $this->client->getResponse()->getContent());
    }

    public function testAddSuiviSignalementError(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);
        $route = $this->router->generate('back_signalement_add_suivi', ['uuid' => $signalement->getUuid()]);
        $csrfToken = $this->generateCsrfToken($this->client, 'add_suivi');
        $this->client->request('POST', $route, [
            'add_suivi' => [
                'description' => 'Je v',
                'isPublic' => '1',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertStringContainsString('Le contenu du suivi doit contenir au moins 10 caract\u00e8res.', $this->client->getResponse()->getContent());
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteSuivi(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);

        $route = $this->router->generate('back_signalement_delete_suivi', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $description = 'Un petit message de rappel afin d&#039;y revenir plus tard';
        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);

        $this->client->request(
            'POST',
            $route,
            [
                'suivi' => $suivi->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_suivi_'.$signalement->getId()),
            ]
        );

        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $this->assertNotNull($suivi->getDeletedAt());
        $this->assertNotNull($suivi->getDeletedBy());
        $this->assertNotEquals($description, $suivi->getDescription());
        $this->assertStringContainsString(Suivi::DESCRIPTION_DELETED, $suivi->getDescription());
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid().'#suivis');
    }

    public function testSwitchValue(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000010']);

        $route = $this->router->generate('back_signalement_switch_value', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 1,
                '_token' => $this->generateCsrfToken($this->client, 'KO'),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"error"}', $this->client->getResponse()->getContent());

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 1,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_value_'.$signalement->getUuid()),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"success"}', $this->client->getResponse()->getContent());

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 3,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_value_'.$signalement->getUuid()),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"success"}', $this->client->getResponse()->getContent());
        $this->assertEquals(1, $signalement->getTags()->count());
        $this->assertEquals(3, $signalement->getTags()->first()->getId());
    }

    /**
     * @dataProvider provideSignalementToSetRnbId
     */
    public function testsetRnbId(string $uuid, bool $isGeolocUpdated): void
    {
        $buildingData = json_decode(file_get_contents(__DIR__.'/../../../files/betagouv/get_api_rnb_buildings_response.json'), true);
        $building = new RnbBuilding($buildingData['results'][0]);
        $rnbService = $this->createMock(RnbService::class);
        if ($isGeolocUpdated) {
            $rnbService->expects($this->once())
            ->method('getBuilding')
            ->willReturn($building);
        } else {
            $rnbService->expects($this->never())
            ->method('getBuilding');
        }
        self::getContainer()->set(RnbService::class, $rnbService);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => $uuid]);

        $route = $this->router->generate('back_signalement_set_rnb', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'POST',
            $route,
            [
                'rnbId' => 'FQYN6F6WPEJ8',
                '_token' => $this->generateCsrfToken($this->client, 'signalement_set_rnb_'.$signalement->getUuid()),
            ]
        );
        if ($isGeolocUpdated) {
            $this->assertEquals('FQYN6F6WPEJ8', $signalement->getRnbIdOccupant());
            $this->assertEquals(['lat' => 44.05309187516625, 'lng' => 4.141756415466935], $signalement->getGeoloc());
        } else {
            $this->assertNull($signalement->getRnbIdOccupant());
        }
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function provideSignalementToSetRnbId(): \Generator
    {
        yield 'Signalement without geoloc' => ['00000000-0000-0000-2025-000000000004', true];
        yield 'Signalement with geoloc' => ['00000000-0000-0000-2025-000000000003', false];
    }
}
