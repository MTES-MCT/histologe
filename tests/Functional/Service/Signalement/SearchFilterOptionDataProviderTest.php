<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Repository\BailleurRepository;
use App\Repository\CommuneRepository;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
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
    private SearchFilterOptionDataProvider $searchFilterOptionDataProvider;
    private CritereRepository $critereRepository;
    private TerritoryRepository $territoryRepository;
    private CommuneRepository $communeRepository;
    private PartnerRepository $partnerRepository;
    private TagRepository $tagsRepository;
    private SignalementRepository $signalementRepository;
    private BailleurRepository $bailleurRepository;
    private TagAwareCacheInterface $cache;
    private QualificationStatusService $qualificationStatusService;
    private ZoneRepository $zoneRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->critereRepository = self::getContainer()->get(CritereRepository::class);
        $this->territoryRepository = self::getContainer()->get(TerritoryRepository::class);
        $this->communeRepository = self::getContainer()->get(CommuneRepository::class);
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);
        $this->tagsRepository = self::getContainer()->get(TagRepository::class);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $this->bailleurRepository = self::getContainer()->get(BailleurRepository::class);
        $this->cache = self::getContainer()->get(TagAwareCacheInterface::class);
        $this->qualificationStatusService = self::getContainer()->get(QualificationStatusService::class);
        $this->zoneRepository = self::getContainer()->get(ZoneRepository::class);

        $this->searchFilterOptionDataProvider = new SearchFilterOptionDataProvider(
            $this->critereRepository,
            $this->territoryRepository,
            $this->communeRepository,
            $this->partnerRepository,
            $this->tagsRepository,
            $this->signalementRepository,
            $this->cache,
            $this->qualificationStatusService,
            $this->bailleurRepository,
            $this->zoneRepository
        );
    }

    public function testGetData(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $expectedData = [
            'criteres' => $this->critereRepository->findAllList(),
            'territories' => $user->isSuperAdmin() ? $this->territoryRepository->findAllList() : $user->getPartnersTerritories(),
            'partners' => $this->partnerRepository->findAllList(null, $user),
            'tags' => $this->tagsRepository->findAllActive(null, $user),
            'cities' => $this->signalementRepository->findCities($user),
        ];

        $actualData = $this->searchFilterOptionDataProvider->getData($user);
        $this->assertSameSize($expectedData['criteres'], $actualData['criteres']);
        $this->assertSameSize($expectedData['territories'], $actualData['territories']);
        $this->assertSameSize($expectedData['partners'], $actualData['partners']);
        $this->assertSameSize($expectedData['tags'], $actualData['tags']);
        $this->assertSameSize($expectedData['cities'], $actualData['cities']);
    }
}
