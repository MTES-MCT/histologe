<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Faker\Factory;
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
    private RouterInterface $router;
    private $faker;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->faker = Factory::create();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
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
                'visite-add[date]' => '2123-01-01',
                'visite-add[partner]' => $partner->getId(),
                '_token' => 'signalement_add_visit_'.$signalement->getId(),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
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
                'visite-add[date]' => '2022-01-01',
                'visite-add[partner]' => $partner->getId(),
                'visite-add[details]' => 'Lorem Ipsum',
                '_token' => 'signalement_add_visit_'.$signalement->getId(),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testAddPastVisiteNotDone(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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
                    'visiteDone' => '0',
                    'occupantPresent' => '0',
                    'proprietairePresent' => '0',
                    'notifyUsager' => '0',
                    'details' => 'Lorem Ipsum',
                ],
                '_token' => $this->generateCsrfToken($this->client, 'signalement_add_visit_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->assertEmailCount(3);
    }
}
