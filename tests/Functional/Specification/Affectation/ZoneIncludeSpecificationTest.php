<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\ZoneIncludeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZoneIncludeSpecificationTest extends KernelTestCase
{
    /**
     * @param ?array<string> $zoneToInclude
     * @param array<int>     $signalementZoneIds
     */
    #[DataProvider('provideRulesAndSignalementZones')]
    public function testIsSatisfiedBy(?array $zoneToInclude, array $signalementZoneIds, bool $isSatisfied): void
    {
        $specification = new ZoneIncludeSpecification($zoneToInclude, $signalementZoneIds);
        $context = new PartnerSignalementContext(new Partner(), new Signalement());

        $this->assertSame($isSatisfied, $specification->isSatisfiedBy($context));
    }

    public static function provideRulesAndSignalementZones(): \Generator
    {
        yield 'no zone in rule (null)' => [null, [], true];
        yield 'no zone in rule (empty)' => [[], [1, 2], true];
        yield 'signalement in no zone' => [['1'], [], false];
        yield 'signalement in the rule zone' => [['1'], [1], true];
        yield 'signalement in one of the rule zones' => [['1', '3'], [2, 3], true];
        yield 'signalement in other zones' => [['1', '3'], [2, 4], false];
    }
}
