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
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
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
                'visite-add' => [
                    'date' => '2123-01-01',
                    'time' => '',
                    'partner' => $partner->getId(),
                    'externalOperator' => '',
                    'commentBeforeVisite' => 'Commentaire avant visite',
                ],
                '_token' => $this->generateCsrfToken(
                    $this->client,
                    'signalement_add_visit_'.$signalement->getId()
                ),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());

        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals('La date de visite a bien été définie.', $successMessages[0]);

        $this->client->followRedirect();
        $crawler = $this->client->getCrawler();
        $highlights = $crawler->filter('.fr-highlight');
        $this->assertCount(2, $highlights);
        $this->assertStringContainsString('Commentaire avant visite', $highlights->eq(1)->text());
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
                'visite-add' => [
                    'date' => '2123-01-01',
                    'time' => '10:00',
                    'partner' => 'extern',
                    'externalOperator' => 'Opérateur externe',
                ],
                '_token' => $this->generateCsrfToken(
                    $this->client,
                    'signalement_add_visit_'.$signalement->getId()
                ),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());

        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals('La date de visite a bien été définie.', $successMessages[0]);
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
                'visite-add' => [
                    'date' => '2022-01-01',
                    'time' => '10:00',
                    'partner' => $partner->getId(),
                    'externalOperator' => '',
                    'details' => 'Lorem Ipsum',
                ],
                '_token' => $this->generateCsrfToken(
                    $this->client,
                    'signalement_add_visit_'.$signalement->getId()
                ),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals('Les informations de la visite ont bien été renseignées.', $successMessages[0]);
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
                'visite-add' => [
                    'date' => '2023-01-01',
                    'time' => '10:00',
                    'partner' => $partner->getId(),
                    'externalOperator' => '',
                    'visiteDone' => '0',
                    'occupantPresent' => '0',
                    'proprietairePresent' => '0',
                    'notifyUsager' => '0',
                    'details' => 'Lorem Ipsum',
                ],
                '_token' => $this->generateCsrfToken(
                    $this->client,
                    'signalement_add_visit_'.$signalement->getId()
                ),
            ]
        );

        $intervention = $this->interventionRepository->findOneBy([
            'signalement' => $signalement,
            'status' => Intervention::STATUS_NOT_DONE]
        );
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->assertEquals('2023-01-01 09:00', $intervention->getScheduledAt()->format('Y-m-d H:i'));

        $this->assertEmailCount(2);
    }

    public function testcancelVisiteFromSignalement(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $route = $this->router->generate('back_signalement_visite_cancel', ['uuid' => $signalement->getUuid()]);
        $this->client->request('POST', $route, ['visite-cancel' => [
            'intervention' => $signalement->getInterventions()->first()->getId(), 'details' => 'nanana',
        ]]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testcancelVisiteFromSignalementDeny(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000003']);

        $route = $this->router->generate('back_signalement_visite_cancel', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'POST',
            $route,
            ['visite-cancel' => [
                'intervention' => $signalement->getInterventions()->first()->getId(), 'details' => 'nanana'],
            ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditPastVisiteDone(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000010']);
        $interventionId = $signalement->getInterventions()->first()->getId();

        $route = $this->router->generate('back_signalement_visite_edit', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'visite-edit' => [
                    'concludeProcedure' => [
                        'NON_DECENCE',
                        'RSD',
                        'INSALUBRITE',
                    ],
                    'details' => 'Hello world',
                    'intervention' => $interventionId,
                ],
                '_token' => $this->generateCsrfToken(
                    $this->client,
                    'signalement_edit_visit_'.$interventionId
                ),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());

        $this->assertEmailCount(1);
    }
}
