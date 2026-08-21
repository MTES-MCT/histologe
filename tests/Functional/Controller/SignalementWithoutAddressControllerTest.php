<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementWithoutAddressControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;
    private TerritoryRepository $territoryRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testSignalementWithoutAddressIndex(): void
    {
        $count = $this->signalementRepository->count(['address' => null]);

        $route = $this->router->generate('back_signalement_without_address_index');
        $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();

        $expectedLabel = $count > 1 ? 'signalements sans adresse trouvés' : 'signalement sans adresse trouvé';
        $this->assertSelectorTextContains('h2#desc-table', $count.' '.$expectedLabel);
    }

    public function testSignalementWithoutAddressIndexIsRestrictedToAdmin(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_without_address_index');
        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSearchAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_search', [
            'uuid' => $signalement->getUuid(),
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertSame('10 Rue de la Paix Paris', $responseData['query']);
        $this->assertCount(1, $responseData['results']);
        $this->assertSame('75102', $responseData['results'][0]['properties']['citycode']);
    }

    public function testLinkAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_link', [
            'uuid' => $signalement->getUuid(),
        ]);

        $feature = [
            'type' => 'Feature',
            'properties' => [
                'label' => '10 Rue de la Paix 75002 Paris',
                'housenumber' => '10',
                'name' => '10 Rue de la Paix',
                'street' => 'Rue de la Paix',
                'postcode' => '75002',
                'citycode' => '75102',
                'city' => 'Paris',
                'id' => '75102_7060_00010',
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [2.330, 48.869],
            ],
        ];

        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_link_address_'.$signalement->getId()),
            'feature' => json_encode($feature),
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['closeModal']);

        $this->assertNotNull($signalement->getAddress());
        $this->assertSame('75002', $signalement->getAddress()->getPostCode());
        $this->assertSame('75102', $signalement->getAddress()->getCityCode());
        $this->assertSame('75102_7060_00010', $signalement->getAddress()->getBanId());
    }

    public function testLinkAddressWithInvalidCsrfToken(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['address' => null]);

        $route = $this->router->generate('back_signalement_without_address_link', [
            'uuid' => $signalement->getUuid(),
        ]);

        $this->client->request('POST', $route, [
            '_token' => 'invalid-token',
            'feature' => json_encode(['properties' => ['postcode' => '75002', 'citycode' => '75102']]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getAddress());
    }

    private function prepareSignalementForParisSearch(): Signalement
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['address' => null]);
        $paris = $this->territoryRepository->findOneBy(['zip' => '75']);

        $signalement
            ->setAdresseOccupant('10 Rue de la Paix')
            ->setVilleOccupant('Paris')
            ->setCpOccupant('')
            ->setInseeOccupant(null)
            ->setTerritory($paris);
        $this->entityManager->flush();

        return $signalement;
    }
}
