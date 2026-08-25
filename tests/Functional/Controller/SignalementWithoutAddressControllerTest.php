<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchSignalementWithoutAddress;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementWithoutAddressControllerTest extends WebTestCase
{
    use SessionHelper;

    private const string PARIS_ADDRESS_LABEL = '10 Rue de la Paix';
    private const string PARIS_POSTAL_CODE = '75002';
    private const string PARIS_INSEE_CODE = '75102';
    private const string PARIS_BAN_ID = '75102_7060_00010';

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

    public function testBothActionButtonsShowWhenTerritoryAndCpInseeAreBothInconsistent(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['address' => null]);
        $loireAtlantique = $this->territoryRepository->findOneBy(['zip' => '44']);

        // CP et INSEE valides individuellement mais incohérents entre eux (aucune commune 44/54 n'existe),
        // et le territoire assigné (Loire-Atlantique) ne correspond pas au territoire calculé depuis
        // l'INSEE (54 - Meurthe-et-Moselle) : les deux anomalies, et donc les deux actions, doivent apparaître.
        $signalement
            ->setCpOccupant('44420')
            ->setInseeOccupant('54570')
            ->setTerritory($loireAtlantique);
        $this->entityManager->flush();

        $route = $this->router->generate('back_signalement_without_address_index', ['territory' => $loireAtlantique->getId()]);
        $crawler = $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $row = $crawler->filter('tr:contains("#'.$signalement->getReference().'")');
        $this->assertCount(1, $row->filter('.btn-change-territory'));
        $this->assertCount(1, $row->filter('.btn-search-address'));
    }

    public function testBulkLinkButtonPreviewUrlIncludesCurrentPage(): void
    {
        $route = $this->router->generate('back_signalement_without_address_index', ['page' => 2]);
        $crawler = $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $previewUrl = $crawler->filter('.btn-bulk-link-address')->attr('data-preview-url');
        $this->assertStringContainsString('page=2', (string) $previewUrl);
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

        $this->assertSame(self::PARIS_ADDRESS_LABEL.' Paris', $responseData['query']);
        $this->assertCount(1, $responseData['results']);
        $this->assertSame(self::PARIS_INSEE_CODE, $responseData['results'][0]['properties']['citycode']);
    }

    public function testLinkAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_link', [
            'uuid' => $signalement->getUuid(),
        ]);

        $feature = $this->parisBanFeature();

        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_link_address_'.$signalement->getId()),
            'feature' => json_encode($feature),
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['closeModal']);

        $this->assertNotNull($signalement->getAddress());
        $this->assertSame(self::PARIS_POSTAL_CODE, $signalement->getAddress()->getPostCode());
        $this->assertSame(self::PARIS_INSEE_CODE, $signalement->getAddress()->getCityCode());
        $this->assertSame(self::PARIS_BAN_ID, $signalement->getAddress()->getBanId());
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
            'feature' => json_encode(['properties' => ['postcode' => self::PARIS_POSTAL_CODE, 'citycode' => self::PARIS_INSEE_CODE]]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getAddress());
    }

    public function testExport(): void
    {
        $route = $this->router->generate('back_signalement_without_address_export');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="signalements-sans-adresse_', (string) $response->headers->get('Content-Disposition'));
    }

    public function testExportIsRestrictedToAdmin(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_without_address_export');
        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBulkLinkAddressPreview(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_bulk_link_preview', [
            'territory' => $signalement->getTerritory()?->getId(),
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        // au moins notre candidat ; d'autres signalements de fixtures déjà valides et assignés
        // au même territoire peuvent aussi être éligibles (la condition n'exclut que
        // INCONSISTENT_TERRITORY, pas seulement MISSING_CP_AND_INSEE), donc pas de count exact ici.
        $this->assertGreaterThanOrEqual(1, $responseData['count']);
        $this->assertStringContainsString($signalement->getReference(), $responseData['html']);
        $this->assertStringContainsString(self::PARIS_ADDRESS_LABEL.' Paris', $responseData['html']);
    }

    public function testBulkLinkAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $candidates = [[
            'uuid' => $signalement->getUuid(),
            'feature' => $this->parisBanFeature(),
        ]];

        // Note : on filtre ici par statut (et non territoire) car TerritoryChoiceType ne liste que les
        // territoires actifs ; soumettre un territoire inactif via search_params rendrait le formulaire
        // invalide et réinitialiserait tous les filtres, ce qui n'est pas ce qu'on veut isoler ici.
        $searchParams = http_build_query(['statut' => SignalementStatus::ACTIVE->value]);

        $route = $this->router->generate('back_signalement_without_address_bulk_link');
        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_bulk_link_address'),
            'candidates' => json_encode($candidates),
            'search_params' => $searchParams,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['closeModal']);
        $this->assertStringContainsString('1 signalement', $responseData['flashMessages'][0]['message']);

        $this->assertNotNull($signalement->getAddress());
        $this->assertSame(self::PARIS_INSEE_CODE, $signalement->getAddress()->getCityCode());

        // le fragment de liste renvoyé doit conserver le filtre de statut (régression : search_params manquant côté panneau)
        $this->assertStringContainsString(
            'statut=ACTIVE',
            $responseData['htmlTargetContents'][0]['content']
        );
    }

    public function testBulkLinkAddressWithInvalidCsrfToken(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $candidates = [[
            'uuid' => $signalement->getUuid(),
            'feature' => ['properties' => ['postcode' => self::PARIS_POSTAL_CODE, 'citycode' => self::PARIS_INSEE_CODE]],
        ]];

        $route = $this->router->generate('back_signalement_without_address_bulk_link');
        $this->client->request('POST', $route, [
            '_token' => 'invalid-token',
            'candidates' => json_encode($candidates),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getAddress());
    }

    public function testFindSignalementsWithoutAddressIgnoresPagination(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $search = new SearchSignalementWithoutAddress();
        $search->setTerritory($signalement->getTerritory());

        $results = $this->signalementRepository->findSignalementsWithoutAddress($search);

        $this->assertNotEmpty($results);
        $this->assertContainsEquals($signalement, $results);
        foreach ($results as $result) {
            $this->assertSame($signalement->getTerritory()?->getId(), $result->getTerritory()?->getId());
            $this->assertNull($result->getAddress());
        }
    }

    private function prepareSignalementForParisSearch(): Signalement
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['address' => null]);
        $paris = $this->territoryRepository->findOneBy(['zip' => '75']);

        $signalement
            ->setAdresseOccupant(self::PARIS_ADDRESS_LABEL)
            ->setVilleOccupant('Paris')
            ->setCpOccupant('')
            ->setInseeOccupant(null)
            ->setTerritory($paris);
        $this->entityManager->flush();

        return $signalement;
    }

    /**
     * @return array<string, mixed>
     */
    private function parisBanFeature(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'label' => self::PARIS_ADDRESS_LABEL.' '.self::PARIS_POSTAL_CODE.' Paris',
                'housenumber' => '10',
                'name' => self::PARIS_ADDRESS_LABEL,
                'street' => 'Rue de la Paix',
                'postcode' => self::PARIS_POSTAL_CODE,
                'citycode' => self::PARIS_INSEE_CODE,
                'city' => 'Paris',
                'id' => self::PARIS_BAN_ID,
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [2.330, 48.869],
            ],
        ];
    }
}
