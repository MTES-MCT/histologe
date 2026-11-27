<?php

namespace App\Tests\Functional\Repository\Query\Signalement;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\Query\Signalement\SignalementMassAffectationQuery;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementMassAffectationQueryTest extends KernelTestCase
{
    private SignalementMassAffectationQuery $query;
    private Territory $territory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->query = static::getContainer()->get(SignalementMassAffectationQuery::class);
        /** @var Territory $territory */
        $territory = static::getContainer()->get(TerritoryRepository::class)->findOneBy(['zip' => '13']);
        $this->territory = $territory;
    }

    public function testCountsActiveSignalementsOfTerritory(): void
    {
        $ids = $this->getIds(['2022-4', '2022-8']);

        $this->assertSame(2, $this->query->countActiveInTerritory($ids, $this->territory));
    }

    public function testIgnoresNotActiveSignalements(): void
    {
        $ids = $this->getIds(['2022-4', '2022-8', '2022-3']);

        $this->assertSame(2, $this->query->countActiveInTerritory($ids, $this->territory));
    }

    public function testIgnoresSignalementsOfOtherTerritory(): void
    {
        $ids = $this->getIds(['2022-4', '2022-8', '2024-08']);

        $this->assertSame(2, $this->query->countActiveInTerritory($ids, $this->territory));
    }

    public function testEmptyIdsReturnsZero(): void
    {
        $this->assertSame(0, $this->query->countActiveInTerritory([], $this->territory));
    }

    /**
     * @param array<string> $references
     *
     * @return array<int>
     */
    private function getIds(array $references): array
    {
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);

        return array_map(
            static function (string $reference) use ($signalementRepository): int {
                /** @var Signalement $signalement */
                $signalement = $signalementRepository->findOneBy(['reference' => $reference]);

                return (int) $signalement->getId();
            },
            $references
        );
    }
}
