<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Repository\BailleurRepository;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\Query\Address\AddressesHistoryQuery;
use App\Repository\Query\Commune\CommuneEpciQuery;
use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class SearchFilterOptionDataProviderTest extends KernelTestCase
{
    private readonly SearchFilterOptionDataProvider $searchFilterOptionDataProvider;
    private readonly CritereRepository $critereRepository;
    private readonly TerritoryRepository $territoryRepository;
    private readonly PartnerRepository $partnerRepository;
    private readonly TagRepository $tagsRepository;
    private readonly SignalementRepository $signalementRepository;
    private readonly AddressesHistoryQuery $addressesHistoryQuery;
    private readonly BailleurRepository $bailleurRepository;
    private readonly TagAwareCacheInterface $cache;
    private readonly QualificationStatusService $qualificationStatusService;
    private readonly ZoneRepository $zoneRepository;
    private readonly CountStatisticsQuery $countStatisticsQuery;
    private readonly CommuneEpciQuery $communeEpciQuery;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->critereRepository = static::getContainer()->get(CritereRepository::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->tagsRepository = static::getContainer()->get(TagRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->addressesHistoryQuery = static::getContainer()->get(AddressesHistoryQuery::class);
        $this->bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $this->cache = static::getContainer()->get(TagAwareCacheInterface::class);
        $this->qualificationStatusService = static::getContainer()->get(QualificationStatusService::class);
        $this->zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $this->countStatisticsQuery = static::getContainer()->get(CountStatisticsQuery::class);
        $this->communeEpciQuery = static::getContainer()->get(CommuneEpciQuery::class);
        $this->searchFilterOptionDataProvider = new SearchFilterOptionDataProvider(
            $this->critereRepository,
            $this->territoryRepository,
            $this->partnerRepository,
            $this->tagsRepository,
            $this->signalementRepository,
            $this->addressesHistoryQuery,
            $this->cache,
            $this->qualificationStatusService,
            $this->bailleurRepository,
            $this->zoneRepository,
            $this->countStatisticsQuery,
            $this->communeEpciQuery,
        );
    }

    public function testGetData(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        /** @var array<int, mixed> $criteres */
        $criteres = (array) $this->critereRepository->findAllList();
        /** @var array<int, mixed> $territories */
        $territories = (array) ($user->isSuperAdmin() ? $this->territoryRepository->findAllList() : $user->getPartnersTerritories());
        /** @var array<int, mixed> $partners */
        $partners = (array) $this->partnerRepository->findAllList(null, $user);
        /** @var array<int, mixed> $tags */
        $tags = (array) $this->tagsRepository->findAllActive(null, $user);
        /** @var array<int, mixed> $cities */
        $cities = (array) $this->signalementRepository->findCities($user);

        $expectedData = compact('criteres', 'territories', 'partners', 'tags', 'cities');

        $actualData = $this->searchFilterOptionDataProvider->getData($user);
        $this->assertSameSize($expectedData['criteres'], $actualData['criteres']);
        $this->assertSameSize($expectedData['territories'], $actualData['territories']);
        $this->assertSameSize($expectedData['partners'], $actualData['partners']);
        $this->assertSameSize($expectedData['tags'], $actualData['tags']);
        $this->assertSameSize($expectedData['cities'], $actualData['cities']);
    }

    public function testGetDataWithContexts(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        // Test sans contexte
        $dataWithoutContext = $this->searchFilterOptionDataProvider->getData($user, null, null);
        $this->assertArrayHasKey('bailleursSociaux', $dataWithoutContext);
        $this->assertNotEmpty($dataWithoutContext['bailleursSociaux']);
        $this->assertArrayHasKey('addresses', $dataWithoutContext);
        $this->assertEmpty($dataWithoutContext['addresses']);

        // Test avec contexte addresses-history
        $dataWithContext = $this->searchFilterOptionDataProvider->getData($user, null, 'addresses-history');
        $this->assertArrayHasKey('bailleursSociaux', $dataWithContext);
        $this->assertIsArray($dataWithContext['bailleursSociaux']);
        $this->assertContains('Habitat 44', $dataWithContext['bailleursSociaux']);
        $this->assertArrayHasKey('addresses', $dataWithContext);
        $this->assertNotEmpty($dataWithContext['addresses']);
    }
}
