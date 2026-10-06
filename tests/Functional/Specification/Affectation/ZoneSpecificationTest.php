<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\ZoneSpecification;
use App\Specification\Context\PartnerSignalementContext;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZoneSpecificationTest extends KernelTestCase
{
    /**
     * @param ?array<string> $zoneToInclude
     * @param ?array<string> $zoneToExclude
     * @param array<int>     $signalementZoneIds
     */
    #[DataProvider('provideRulesAndSignalementZones')]
    public function testIsSatisfiedBy(?array $zoneToInclude, ?array $zoneToExclude, array $signalementZoneIds, bool $isSatisfied): void
    {
        $specification = new ZoneSpecification($zoneToInclude, $zoneToExclude, $signalementZoneIds);
        $context = new PartnerSignalementContext(new Partner(), new Signalement());

        $this->assertSame($isSatisfied, $specification->isSatisfiedBy($context));
    }

    public static function provideRulesAndSignalementZones(): \Generator
    {
        yield 'no zone in rule (null)' => [null, null, [], true];
        yield 'no zone in rule (empty)' => [[], [], [1, 2], true];

        yield 'include - signalement in no zone' => [['1'], null, [], false];
        yield 'include - signalement in the rule zone' => [['1'], null, [1], true];
        yield 'include - signalement in one of the rule zones' => [['1', '3'], null, [2, 3], true];
        yield 'include - signalement in other zones' => [['1', '3'], null, [2, 4], false];

        yield 'exclude - signalement in no zone' => [null, ['1'], [], true];
        yield 'exclude - signalement in the excluded zone' => [null, ['1'], [1], false];
        yield 'exclude - signalement in one of the excluded zones' => [null, ['1', '3'], [2, 3], false];
        yield 'exclude - signalement in other zones' => [null, ['1', '3'], [2, 4], true];

        yield 'include and exclude - signalement in included zone only' => [['1'], ['2'], [1], true];
        yield 'include and exclude - signalement in both zones' => [['1'], ['2'], [1, 2], false];
        yield 'include and exclude - signalement in excluded zone only' => [['1'], ['2'], [2], false];
        yield 'include and exclude - signalement in no zone' => [['1'], ['2'], [], false];
    }
}
