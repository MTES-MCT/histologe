<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Intervention;
use App\Repository\InterventionRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementVisitesControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private PartnerRepository $partnerRepository;
    private SignalementRepository $signalementRepository;
    private InterventionRepository $interventionRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->interventionRepository = static::getContainer()->get(InterventionRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testAddFutureVisite(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $route = $this->router->generate('back_signalement_visite_add', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-01']);

        $this->client->request(
            'POST',
            $route,
            [
                'add_and_reschedule_visite' => [
                    'scheduledAt' => '2123-01-01',
                    'scheduledAtTime' => '10:00',
                    'partnerChoice' => $partner->getId(),
                    'externalOperator' => '',
                    'commentBeforeVisite' => 'Commentaire avant visite',
                    '_token' => $this->generateCsrfToken($this->client, 'add_and_reschedule_visite'),
                ],
            ]
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'La date de visite a bien été définie.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);

        $route = $this->router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        $crawler = $this->client->request('GET', $route);
        $highlights = $crawler->filter('.fr-highlight');
        $this->assertCount(2, $highlights);
        $this->assertStringContainsString('Commentaire avant visite', $highlights->eq(0)->text());
    }

    public function testAddFutureVisiteOnExternalOperator(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $route = $this->router->generate('back_signalement_visite_add', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'add_and_reschedule_visite' => [
                    'scheduledAt' => '2123-01-01',
                    'scheduledAtTime' => '10:00',
                    'partnerChoice' => 'extern',
                    'externalOperator' => 'Opérateur externe',
                    '_token' => $this->generateCsrfToken($this->client, 'add_and_reschedule_visite'),
                ],
            ]
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'La date de visite a bien été définie.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testVisiteOnLogementVacant(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000010']);
        $route = $this->router->generate('back_signalement_visite_add', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'add_and_reschedule_visite' => [
                    'scheduledAt' => '2123-01-01',
                    'scheduledAtTime' => '10:00',
                    'partnerChoice' => 'extern',
                    'externalOperator' => 'Opérateur externe',
                    '_token' => $this->generateCsrfToken($this->client, 'add_and_reschedule_visite'),
                ],
            ]
        );
        $this->assertEmailCount(0);
    }

    public function testAddPastVisite(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $route = $this->router->generate('back_signalement_visite_add', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-01']);

        $this->client->request(
            'POST',
            $route,
            [
                'add_and_reschedule_visite' => [
                    'scheduledAt' => '2022-01-01',
                    'scheduledAtTime' => '10:00',
                    'partnerChoice' => $partner->getId(),
                    'externalOperator' => '',
                    'visiteDone' => '1',
                    'occupantPresent' => '1',
                    'proprietairePresent' => '1',
                    'concludeProcedure' => ['RSD'],
                    'details' => 'Lorem Ipsum dolor sit amet',
                    '_token' => $this->generateCsrfToken($this->client, 'add_and_reschedule_visite'),
                ],
            ]
        );
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'Les informations de la visite ont bien été enregistrées.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testAddPastVisiteNotDone(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000001']);

        $route = $this->router->generate('back_signalement_visite_add', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 62-01']);

        $this->client->request(
            'POST',
            $route,
            [
                'add_and_reschedule_visite' => [
                    'scheduledAt' => '2023-01-01',
                    'scheduledAtTime' => '10:00',
                    'partnerChoice' => $partner->getId(),
                    'externalOperator' => '',
                    'visiteDone' => '0',
                    'occupantPresent' => '0',
                    'proprietairePresent' => '0',
                    'details' => 'Lorem Ipsum dolor sit amet',
                    '_token' => $this->generateCsrfToken($this->client, 'add_and_reschedule_visite'),
                ],
            ]
        );

        $intervention = $this->interventionRepository->findOneBy([
            'signalement' => $signalement,
            'status' => Intervention::STATUS_NOT_DONE]
        );
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'Les informations de la visite ont bien été enregistrées.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
        $this->assertEmailCount(2);
        $this->assertEquals('2023-01-01 09:00', $intervention->getScheduledAt()->format('Y-m-d H:i'));
    }

    public function testcancelVisiteFromSignalement(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);
        $intervention = $signalement->getInterventions()->first();
        if (!$intervention) {
            $this->fail('No intervention found for the signalement');
        }

        $route = $this->router->generate('back_signalement_visite_cancel', ['id' => $intervention->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'cancel_visite' => [
                    'details' => 'nanana',
                    '_token' => $this->generateCsrfToken($this->client, 'cancel_visite'),
                ],
            ]
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'La visite a bien été annulée.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testcancelVisiteFromSignalementDeny(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000003']);
        $intervention = $signalement->getInterventions()->first();
        if (!$intervention) {
            $this->fail('No intervention found for the signalement');
        }

        $route = $this->router->generate('back_signalement_visite_cancel', ['id' => $intervention->getId()]);
        $this->client->request(
            'POST',
            $route,
            ['cancel_visite' => ['details' => 'nanana']]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditPastVisiteDone(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000010']);
        $intervention = $signalement->getInterventions()->first();
        if (!$intervention) {
            $this->fail('No intervention found for the signalement');
        }
        $interventionId = $intervention->getId();

        $route = $this->router->generate('back_signalement_visite_edit_conclusion', ['id' => $interventionId]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'edit_conclusion_visite' => [
                    'concludeProcedure' => [
                        'NON_DECENCE',
                        'RSD',
                        'INSALUBRITE',
                    ],
                    'notifyUsager' => '1',
                    'details' => 'Hello world, this is a comment',
                    '_token' => $this->generateCsrfToken($this->client, 'edit_conclusion_visite'),
                ],
            ]
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'Les informations de la visite ont bien été enregistrées.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
        $this->assertEmailCount(1);
    }

    public function testEditPastVisiteDoneWithoutNotification(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000010']);
        $intervention = $signalement->getInterventions()->first();
        if (!$intervention) {
            $this->fail('No intervention found for the signalement');
        }
        $interventionId = $intervention->getId();

        $route = $this->router->generate('back_signalement_visite_edit_conclusion', ['id' => $interventionId]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'edit_conclusion_visite' => [
                    'concludeProcedure' => [
                        'NON_DECENCE',
                        'RSD',
                        'INSALUBRITE',
                    ],
                    'details' => 'Hello world, this is a comment',
                    '_token' => $this->generateCsrfToken($this->client, 'edit_conclusion_visite'),
                ],
            ]
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'Les informations de la visite ont bien été enregistrées.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
        $this->assertEmailCount(0);
    }
}
