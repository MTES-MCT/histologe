<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\ZoneExcludeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZoneExcludeSpecificationTest extends KernelTestCase
{
    /**
     * @param ?array<string> $zoneToExclude
     * @param array<int>     $signalementZoneIds
     */
    #[DataProvider('provideRulesAndSignalementZones')]
    public function testIsSatisfiedBy(?array $zoneToExclude, array $signalementZoneIds, bool $isSatisfied): void
    {
        $specification = new ZoneExcludeSpecification($zoneToExclude, $signalementZoneIds);
        $context = new PartnerSignalementContext(new Partner(), new Signalement());

        $this->assertSame($isSatisfied, $specification->isSatisfiedBy($context));
    }

    public static function provideRulesAndSignalementZones(): \Generator
    {
        yield 'no excluded zone in rule (null)' => [null, [1], true];
        yield 'no excluded zone in rule (empty)' => [[], [1, 2], true];
        yield 'signalement in no zone' => [['1'], [], true];
        yield 'signalement in the excluded zone' => [['1'], [1], false];
        yield 'signalement in one of the excluded zones' => [['1', '3'], [2, 3], false];
        yield 'signalement in other zones' => [['1', '3'], [2, 4], true];
    }
}
