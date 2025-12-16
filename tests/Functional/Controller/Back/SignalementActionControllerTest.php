<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Suivi;
use App\Entity\UserSignalementSubscription;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\Gouv\Rnb\Response\RnbBuilding;
use App\Service\Gouv\Rnb\RnbService;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementActionControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private SuiviRepository $suiviRepository;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->suiviRepository = static::getContainer()->get(SuiviRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testValidationResponseAcceptSignalementSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);
        $this->client->loginUser($user);
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000009']);
        $route = $this->router->generate('back_signalement_accept', ['uuid' => $signalement->getUuid()]);
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
        $this->assertSelectorTextContains('.fr-notice--success p', 'Le signalement a bien été accepté.');

        $nbSuiviActive = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::SIGNALEMENT_IS_ACTIVE, 'signalement' => $signalement]);
        $this->assertEquals(1, $nbSuiviActive);
    }

    public function testValidationResponseAcceptSignalementErrorChoiceRT(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000016']);
        $route = $this->router->generate('back_signalement_accept', ['uuid' => $signalement->getUuid()]);
        $route = $this->router->generate('back_signalement_accept', ['uuid' => $signalement->getUuid()]);
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
        $this->assertSelectorTextContains('.fr-notice--alert', 'Vous devez sélectionner les responsables de territoire à abonner au dossier.');
    }

    public function testValidationResponseAcceptSignalementSuccessWithChoiceRT(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $this->client->loginUser($user);
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000016']);
        $route = $this->router->generate('back_signalement_accept', ['uuid' => $signalement->getUuid()]);

        $agents = $this->userRepository->findActiveTerritoryAdminsInPartner($user->getPartnerInTerritory($signalement->getTerritory()));
        $agentIds = [];
        foreach ($agents as $agent) {
            $agentIds[] = $agent->getId();
        }
        $agentIds = array_map(fn ($id) => (string) $id, $agentIds);

        $tokenId = 'agents_selection';

        $this->client->request('POST', $route, [
            'agents_selection' => [
                'agents' => $agentIds,
                '_token' => $this->generateCsrfToken($this->client, $tokenId),
            ],
        ]);

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertEquals('/bo/signalements/'.$signalement->getUuid(), $response['url']);
        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals(['title' => 'Signalement accepté', 'message' => 'Le signalement a bien été accepté.'], $successMessages[0]);

        $nbSuiviActive = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::SIGNALEMENT_IS_ACTIVE, 'signalement' => $signalement]);
        $this->assertEquals(1, $nbSuiviActive);

        $nbSubscriptions = $this->userSignalementSubscriptionRepository->count(['signalement' => $signalement]);
        $this->assertEquals(count($agentIds), $nbSubscriptions);
    }

    public function testValidationResponseSignalementError(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000016']);
        $route = $this->router->generate('back_signalement_deny', ['uuid' => $signalement->getUuid()]);
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
        $this->assertStringContainsString('Le message doit contenir au moins 10 caract\u00e8res.', (string) $this->client->getResponse()->getContent());
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
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
        $this->assertStringContainsString('Le choix s\u00e9lectionn\u00e9 est invalide.', (string) $this->client->getResponse()->getContent());
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
        $this->assertStringContainsString('Le contenu du suivi doit contenir au moins 10 caract\u00e8res.', (string) $this->client->getResponse()->getContent());
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testDeleteSuiviLogical(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);

        $route = $this->router->generate('back_signalement_delete_suivi', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $description = 'Un petit message de rappel afin d&#039;y revenir plus tard';
        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $suivi->setCreatedAt(new \DateTimeImmutable('-2 hours'));
        $this->client->getContainer()->get('doctrine')->getManager()->flush();

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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertArrayHasKey('functions', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertEquals([['name' => 'applyFilter']], $response['functions']);
        $msgFlash = 'Le suivi a été supprimé.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testDeleteSuiviPhysical(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);

        $route = $this->router->generate('back_signalement_delete_suivi', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $description = 'Un petit message de rappel afin d&#039;y revenir plus tard';
        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $suivi->setCreatedAt(new \DateTimeImmutable('-2 minutes'));
        $this->client->getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request(
            'POST',
            $route,
            [
                'suivi' => $suivi->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_suivi_'.$signalement->getId()),
            ]
        );

        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertArrayHasKey('functions', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertEquals([['name' => 'applyFilter']], $response['functions']);
        $msgFlash = 'Le suivi a été supprimé.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testEditSuiviSuccess(): void
    {
        $description = 'Un petit message de rappel afin d&#039;y revenir plus tard';
        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $suivi->setCreatedAt(new \DateTimeImmutable('-2 minutes'));
        $this->client->getContainer()->get('doctrine')->getManager()->flush();

        $suiviId = $suivi->getId();
        $route = $this->router->generate('back_signalement_edit_suivi', ['suivi' => $suiviId]);

        $this->client->request(
            'POST',
            $route,
            [
                'add_suivi' => [
                    'isPublic' => '1',
                    'description' => 'Un message de suivi modifié',
                    '_token' => $this->generateCsrfToken($this->client, 'add_suivi'),
                ],
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertResponseStatusCodeSame(200);
        $suivi = $this->suiviRepository->find($suiviId);
        $this->assertEquals('Un message de suivi modifié', $suivi->getDescription());
        $this->assertTrue($suivi->getIsPublic());
    }

    public function testEditSuiviExpired(): void
    {
        $description = 'Un petit message de rappel afin d&#039;y revenir plus tard';
        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $suivi->setCreatedAt(new \DateTimeImmutable('-30 minutes'));
        $this->client->getContainer()->get('doctrine')->getManager()->flush();

        $suiviId = $suivi->getId();
        $route = $this->router->generate('back_signalement_edit_suivi', ['suivi' => $suiviId]);

        $this->client->request(
            'POST',
            $route,
            [
                'add_suivi' => [
                    'isPublic' => '1',
                    'description' => 'Un message de suivi modifié',
                    '_token' => $this->generateCsrfToken($this->client, 'add_suivi'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditSuiviUnauthorized(): void
    {
        $suivi = $this->suiviRepository->findOneBy(['category' => 'SIGNALEMENT_IS_ACTIVE']);
        $suivi->setCreatedAt(new \DateTimeImmutable('-2 minutes'));
        $this->client->getContainer()->get('doctrine')->getManager()->flush();

        $suiviId = $suivi->getId();
        $route = $this->router->generate('back_signalement_edit_suivi', ['suivi' => $suiviId]);

        $this->client->request(
            'POST',
            $route,
            [
                'add_suivi' => [
                    'isPublic' => '1',
                    'description' => 'Un message de suivi modifié',
                    '_token' => $this->generateCsrfToken($this->client, 'add_suivi'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
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
        $this->assertEquals('{"response":"error"}', (string) $this->client->getResponse()->getContent());

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 1,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_value_'.$signalement->getUuid()),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"success"}', (string) $this->client->getResponse()->getContent());

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 3,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_value_'.$signalement->getUuid()),
            ]
        );

        $tag = $signalement->getTags()->first();
        if (!$tag) {
            $this->fail('No tag found for the signalement');
        }
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"success"}', (string) $this->client->getResponse()->getContent());
        $this->assertEquals(1, $signalement->getTags()->count());
        $this->assertEquals(3, $tag->getId());
    }

    /**
     * @dataProvider provideSignalementToSetRnbId
     */
    public function testsetRnbId(string $uuid, bool $isGeolocUpdated): void
    {
        $buildingData = json_decode((string) file_get_contents(__DIR__.'/../../../files/betagouv/get_api_rnb_buildings_response.json'), true);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        if ($isGeolocUpdated) {
            $this->assertArrayHasKey('stayOnPage', $response);
            $this->assertArrayHasKey('flashMessages', $response);
            $this->assertArrayHasKey('closeModal', $response);
            $this->assertArrayHasKey('htmlTargetContents', $response);
            $this->assertTrue($response['stayOnPage']);
            $msgFlash = 'Le bâtiment a bien été mis à jour.';
            $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
        } else {
            $this->assertArrayHasKey('stayOnPage', $response);
            $this->assertArrayHasKey('flashMessages', $response);
            $this->assertTrue($response['stayOnPage']);
            $msgFlash = 'Le signalement a déjà une géolocalisation.';
            $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
        }
    }

    public function provideSignalementToSetRnbId(): \Generator
    {
        yield 'Signalement without geoloc' => ['00000000-0000-0000-2025-000000000004', true];
        yield 'Signalement with geoloc' => ['00000000-0000-0000-2025-000000000003', false];
    }

    public function testSubscribeAndUnsubscribeAlone(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-12']);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_subscribe', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'subscribe'),
            ]
        );
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $this->assertEquals('Vous avez rejoint le dossier, vous apparaissez maintenant dans la liste des agents abonnés au dossier.
        Le dossier apparaît dans vos dossiers sur votre tableau de bord et vous recevrez les mises à jour du dossier.', $flashBag->get('success')[0]);

        $sub = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        $this->assertNotNull($sub);

        $route = $this->router->generate('back_signalement_unsubscribe', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'unsubscribe'),
            ]
        );
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());

        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('error'));
        $this->assertEquals('Vous ne pouvez pas quitter un dossier étant seul agent de votre partenaire.', $flashBag->get('error')[0]);

        $sub = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        $this->assertNotNull($sub);
    }

    public function testSubscribeAndUnsubscribe(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-10']);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-02@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_subscribe', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'subscribe'),
            ]
        );
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $sub = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        $this->assertNotNull($sub);

        $route = $this->router->generate('back_signalement_unsubscribe', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'unsubscribe'),
            ]
        );
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $sub = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        $this->assertNull($sub);
    }

    public function testSubscribeOnUnaffected(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-120']);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_subscribe', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'subscribe'),
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSubscribeAndUnsubscribeAndTransfer(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $user = $this->userRepository->findOneBy(['email' => 'user-partenaire-34-02@signal-logement.fr']);
        $territory = $signalement->getTerritory();
        $partner = $user->getPartnerInTerritory($territory);

        $affectation = (new Affectation())->setPartner($partner)
            ->setSignalement($signalement)
            ->setStatut(AffectationStatus::WAIT)
            ->setTerritory($territory);

        $signalement->addAffectation($affectation);
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($affectation);
        $em->flush();
        $em->refresh($affectation);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_subscribe', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'GET',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'subscribe'),
            ]
        );
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $sub = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        $this->assertNotNull($sub);
        $partnerUsers = $partner->getUsers();
        $otherAgent = $partnerUsers->filter(fn ($u) => $u->getId() !== $user->getId())->first();
        if (!$otherAgent) {
            $this->fail('No other agent found for the partner');
        }

        $route = $this->router->generate('back_signalement_unsubscribe', ['uuid' => $signalement->getUuid()]);

        $this->client->request('POST', $route, [
            'agents_selection' => [
                'agents' => [$otherAgent->getId()],
                '_token' => $this->generateCsrfToken($this->client, 'agents_selection'),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]));
        $this->assertNotNull($this->userSignalementSubscriptionRepository->findOneBy(['user' => $otherAgent, 'signalement' => $signalement]));
    }

    public function testUnsubscribeWithInvalidCsrfToken(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-10']);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-02@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_unsubscribe', ['uuid' => $signalement->getUuid()]);

        $this->client->request('GET', $route, [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());

        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('error'));
        $this->assertEquals('Le jeton CSRF est invalide. Veuillez réessayer.', $flashBag->get('error')[0]);

        $sub = $this->userSignalementSubscriptionRepository->findOneBy([
            'user' => $user,
            'signalement' => $signalement,
        ]);
        $this->assertNotNull($sub);
    }

    public function testSubscribeList(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-08']);
        $user = $this->userRepository->findOneBy(['email' => 'user-partenaire-34-02@signal-logement.fr']);
        $this->client->loginUser($user);
        $sub = new UserSignalementSubscription();
        $sub->setSignalement($signalement);
        $sub->setUser($user);
        $sub->setCreatedBy($user);
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($sub);
        $em->flush();

        $userToSubscribe = $this->userRepository->findOneBy(['email' => 'user-partenaire-multi-ter-34-30@signal-logement.fr']);
        $this->assertNull($this->userSignalementSubscriptionRepository->findOneBy(['user' => $userToSubscribe, 'signalement' => $signalement]));

        $route = $this->router->generate('back_signalement_subscribe_list', ['uuid' => $signalement->getUuid()]);

        $this->client->request('POST', $route, [
            'agents_selection' => [
                'agents' => [$userToSubscribe->getId()],
                '_token' => $this->generateCsrfToken($this->client, 'agents_selection'),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->userSignalementSubscriptionRepository->findOneBy(['user' => $userToSubscribe, 'signalement' => $signalement]));
    }
}
