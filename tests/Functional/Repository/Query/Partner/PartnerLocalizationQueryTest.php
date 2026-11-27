<?php

namespace App\Tests\Functional\Repository\Query\Partner;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\Query\Partner\PartnerLocalizationQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Vérifie que findSignalementIdsInPartnerPerimeter() (version en masse) applique
 * la même règle de périmètre que buildLocalizationQuery() (version unitaire).
 */
class PartnerLocalizationQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PartnerLocalizationQuery $partnerLocalizationQuery;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->partnerLocalizationQuery = static::getContainer()->get(PartnerLocalizationQuery::class);
    }

    /**
     * Territoires choisis pour couvrir chaque branche de la règle de périmètre dans les fixtures.
     *
     * @return iterable<string, array{string}>
     */
    public static function provideTerritoryZip(): iterable
    {
        yield 'Bouches-du-Rhône (INSEE / sans périmètre)' => ['13'];
        yield 'Gard (EPCI)' => ['30'];
        yield 'Hérault (zone + zone exclue)' => ['34'];
        yield 'Loire-Atlantique (zones imbriquées)' => ['44'];
        yield 'Métropole de Lyon (liste INSEE longue)' => ['69'];
    }

    #[DataProvider('provideTerritoryZip')]
    public function testBulkPerimeterMatchesUnitPerimeter(string $zip): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $zip]);
        $this->assertNotNull($territory);

        $signalements = $this->findSignalementsOfTerritory($territory);
        $partners = $this->entityManager->getRepository(Partner::class)->findBy(['territory' => $territory, 'isArchive' => false]);
        $this->assertNotEmpty($signalements, sprintf('Aucun signalement dans les fixtures du territoire %s', $zip));
        $this->assertNotEmpty($partners, sprintf('Aucun partenaire dans les fixtures du territoire %s', $zip));

        $expected = $this->buildExpectedPerimeterWithUnitQuery($signalements);
        $actual = $this->buildActualPerimeterWithBulkQuery($partners, $signalements);

        foreach ($partners as $partner) {
            $partnerId = (int) $partner->getId();
            $expectedIds = $expected[$partnerId] ?? [];
            $actualIds = $actual[$partnerId] ?? [];
            sort($expectedIds);
            sort($actualIds);
            $this->assertSame(
                $expectedIds,
                $actualIds,
                sprintf('Périmètre divergent pour le partenaire "%s" (#%d)', $partner->getNom(), $partnerId)
            );
        }
    }

    public function testEmptySignalementIdsReturnsEmptyArray(): void
    {
        $partner = $this->entityManager->getRepository(Partner::class)->findOneBy(['isArchive' => false]);
        $this->assertNotNull($partner);

        $this->assertSame([], $this->partnerLocalizationQuery->findSignalementIdsInPartnerPerimeter($partner, []));
    }

    /**
     * @return array<Signalement>
     */
    private function findSignalementsOfTerritory(Territory $territory): array
    {
        return $this->entityManager->getRepository(Signalement::class)
            ->createQueryBuilder('s')
            ->innerJoin('s.address', 'a')
            ->where('a.territory = :territory')
            ->setParameter('territory', $territory)
            ->getQuery()
            ->getResult();
    }

    /**
     * Version unitaire : pour chaque signalement, les partenaires qui le couvrent (affectés ou non).
     *
     * @param array<Signalement> $signalements
     *
     * @return array<int, list<int>> ids de signalements indexés par id de partenaire
     */
    private function buildExpectedPerimeterWithUnitQuery(array $signalements): array
    {
        $expected = [];
        foreach ($signalements as $signalement) {
            $partners = $this->partnerLocalizationQuery->findPartnersByLocalization($signalement, true);
            foreach ($partners as $partner) {
                $expected[(int) $partner->getId()][] = (int) $signalement->getId();
            }
        }

        return $expected;
    }

    /**
     * Version en masse : pour chaque partenaire, les signalements de son périmètre.
     *
     * @param array<Partner>     $partners
     * @param array<Signalement> $signalements
     *
     * @return array<int, array<int>> ids de signalements indexés par id de partenaire
     */
    private function buildActualPerimeterWithBulkQuery(array $partners, array $signalements): array
    {
        $signalementIds = array_map(static fn (Signalement $signalement): int => (int) $signalement->getId(), $signalements);
        $actual = [];
        foreach ($partners as $partner) {
            $actual[(int) $partner->getId()] = $this->partnerLocalizationQuery->findSignalementIdsInPartnerPerimeter($partner, $signalementIds);
        }

        return $actual;
    }
}
