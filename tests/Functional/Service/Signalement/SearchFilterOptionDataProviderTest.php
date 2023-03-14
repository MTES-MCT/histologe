<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SearchFilterOptionDataProviderTest extends KernelTestCase
{
    private SearchFilterOptionDataProvider $searchFilterOptionDataProvider;
    private CritereRepository $critereRepository;
    private TerritoryRepository $territoryRepository;
    private PartnerRepository $partnerRepository;
    private TagRepository $tagsRepository;
    private SignalementRepository $signalementRepository;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->critereRepository = self::getContainer()->get(CritereRepository::class);
        $this->territoryRepository = self::getContainer()->get(TerritoryRepository::class);
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);
        $this->tagsRepository = self::getContainer()->get(TagRepository::class);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $this->cache = self::getContainer()->get(CacheInterface::class);

        $this->searchFilterOptionDataProvider = new SearchFilterOptionDataProvider(
            $this->critereRepository,
            $this->territoryRepository,
            $this->partnerRepository,
            $this->tagsRepository,
            $this->signalementRepository,
            $this->cache
        );
    }

    public function testGetData(): void
    {
        $expectedData = [
            'criteres' => $this->critereRepository->findAllList(),
            'territories' => $this->territoryRepository->findAllList(),
            'partners' => $this->partnerRepository->findAllList(),
            'tags' => $this->tagsRepository->findAllActive(),
            'cities' => $this->signalementRepository->findCities(),
        ];

        $cacheKey = 'getData_all_user';

        $actualData = $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) {
                $item->expiresAfter(0);

                return $this->searchFilterOptionDataProvider->getData();
            }
        );

        $this->assertSameSize($expectedData['criteres'], $actualData['criteres']);
        $this->assertSameSize($expectedData['territories'], $actualData['territories']);
        $this->assertSameSize($expectedData['partners'], $actualData['partners']);
        $this->assertSameSize($expectedData['tags'], $actualData['tags']);
        $this->assertSameSize($expectedData['cities'], $actualData['cities']);

        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit());
    }
}
