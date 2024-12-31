<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Zone;
use App\Specification\Affectation\CodeInseeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CodeInseeSpecificationTest extends KernelTestCase
{
    private string $zoneBourgStMars = 'GEOMETRYCOLLECTION(POLYGON ((-1.403246 47.368071, -1.405563 47.369234, -1.409512 47.368652, -1.41346 47.369699, -1.41758 47.368478, -1.417408 47.365571, -1.41758 47.362316, -1.415176 47.359932, -1.410713 47.359583, -1.40625 47.358479, -1.401443 47.358188, -1.400757 47.362374, -1.40316 47.365339, -1.403246 47.368071)), POLYGON ((-1.423073 47.368304, -1.419983 47.369641, -1.420326 47.372024, -1.424017 47.372489, -1.426506 47.371036, -1.42642 47.369583, -1.423073 47.368304)))';
    private string $zoneLaBodiniere = 'POLYGON ((-1.444273 47.350861, -1.448994 47.352024, -1.450453 47.350745, -1.44865 47.349059, -1.446505 47.348593, -1.444273 47.349, -1.444273 47.350861))';

    private array $geolocLaBodiniere = [
        'lat' => 47.349698,
        'lng' => -1.446676,
    ];

    private array $geolocLaTourmentinerie = [
        'lat' => 47.363934,
        'lng' => -1.41422,
    ];

    private array $geolocLaGree = [
        'lat' => 47.37025,
        'lng' => -1.455196,
    ];

    private const string INSEE_STMARS = '44179';
    private const string INSEE_CELLIER = '44028';

    /**
     * @dataProvider provideRulesAndSignalementWithZone
     */
    public function testIsSatisfiedByWithZone(
        ?string $inseeSignalement,
        array $inseePartenaire,
        ?array $inseeToExcludeRule,
        array $geolocSignalement,
        ?string $zoneToInclude,
        ?string $zoneToExclude,
        bool $isSatisfied,
    ): void {
        $partner = new Partner();
        $partner->setInsee($inseePartenaire);
        $this->assertEquals($inseePartenaire, $partner->getInsee());
        if (null !== $zoneToInclude) {
            /** @var Zone $zone */
            $zone = new Zone();
            $zone->setArea($zoneToInclude);
            $partner->addZone($zone);
            $this->assertEquals($zoneToInclude, $partner->getZones()[0]->getArea());
        }
        if (null !== $zoneToExclude) {
            /** @var Zone $zone */
            $zone = new Zone();
            $zone->setArea($zoneToExclude);
            $partner->addExcludedZone($zone);
            $this->assertEquals($zoneToExclude, $partner->getExcludedZones()[0]->getArea());
        }

        $signalement = new Signalement();
        $signalement->setInseeOccupant($inseeSignalement);
        $signalement->setGeoloc($geolocSignalement);
        $this->assertEquals($inseeSignalement, $signalement->getInseeOccupant());

        $specification = new CodeInseeSpecification('partner_list', $inseeToExcludeRule);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalementWithZone(): \Generator
    {
        yield 'same insee as partner - no excluded insee - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, true];
        yield 'same insee as partner - no excluded insee - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'same insee as partner - no excluded insee - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, true];
        yield 'same insee as partner - no excluded insee - no zone - no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, null, null, true];
        yield 'same insee as partner - no excluded insee - no zone - same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'same insee as partner - no excluded insee - no zone - different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, null, $this->zoneBourgStMars, true];
        yield 'same insee as partner - no excluded insee - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaBodiniere, $this->zoneBourgStMars, null, true];
        yield 'same insee as partner - no excluded insee - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'same insee as partner - no excluded insee - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], null, $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, true];

        // tout cela est illogique, et doit renvoyer false
        yield 'same insee as partner - but excluded - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, false];
        yield 'same insee as partner - but excluded - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false];
        yield 'same insee as partner - but excluded - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, false];
        yield 'same insee as partner - but excluded - no zone - no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, null, false];
        yield 'same insee as partner - but excluded - no zone - same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'same insee as partner - but excluded - no zone - different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'same insee as partner - but excluded - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'same insee as partner - but excluded - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'same insee as partner - but excluded - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_STMARS], $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];

        yield 'same insee as partner - another insee excluded - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, true];
        yield 'same insee as partner - another insee excluded - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'same insee as partner - another insee excluded - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, true];
        yield 'same insee as partner - another insee excluded - no zone - no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, null, true];
        yield 'same insee as partner - another insee excluded - no zone - same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'same insee as partner - another insee excluded - no zone - different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, $this->zoneBourgStMars, true];
        yield 'same insee as partner - another insee excluded - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneBourgStMars, null, true];
        yield 'same insee as partner - another insee excluded - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'same insee as partner - another insee excluded - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], [self::INSEE_CELLIER], $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, true];

        yield 'different insee than partner - no excluded insee - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, true];
        yield 'different insee than partner - no excluded insee - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'different insee than partner - no excluded insee - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, true];
        yield 'different insee than partner - no excluded insee - no zone - no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, null, null, false];
        yield 'different insee than partner - no excluded insee - no zone - same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'different insee than partner - no excluded insee - no zone - different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'different insee than partner - no excluded insee - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'different insee than partner - no excluded insee - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'different insee than partner - no excluded insee - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], null, $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];

        yield 'different insee than partner - but excluded - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, false];
        yield 'different insee than partner - but excluded - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'different insee than partner - but excluded - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, false];
        yield 'different insee than partner - but excluded - no zone - no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, null, false];
        yield 'different insee than partner - but excluded - no zone - same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'different insee than partner - but excluded - no zone - different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'different insee than partner - but excluded - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'different insee than partner - but excluded - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'different insee than partner - but excluded - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_STMARS], $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];

        yield 'different insee than partner - another insee excluded - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, true];
        yield 'different insee than partner - another insee excluded - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'different insee than partner - another insee excluded - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, true];
        yield 'different insee than partner - another insee excluded - no zone - no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, null, false];
        yield 'different insee than partner - another insee excluded - no zone - same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'different insee than partner - another insee excluded - no zone - different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'different insee than partner - another insee excluded - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'different insee than partner - another insee excluded - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'different insee than partner - another insee excluded - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], [self::INSEE_CELLIER], $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];

        yield 'partner without insee - no excluded insee - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, true];
        yield 'partner without insee - no excluded insee - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'partner without insee - no excluded insee - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, true];
        yield 'partner without insee - no excluded insee - no zone - no zone excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, null, null, false];
        yield 'partner without insee - no excluded insee - no zone - same zone as excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'partner without insee - no excluded insee - no zone - different zone as excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'partner without insee - no excluded insee - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'partner without insee - no excluded insee - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'partner without insee - no excluded insee - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [], null, $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];

        yield 'partner without insee - but excluded - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, false];
        yield 'partner without insee - but excluded - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'partner without insee - but excluded - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, false];
        yield 'partner without insee - but excluded - no zone - no zone excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, null, false];
        yield 'partner without insee - but excluded - no zone - same zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'partner without insee - but excluded - no zone - different zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'partner without insee - but excluded - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'partner without insee - but excluded - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'partner without insee - but excluded - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_STMARS], $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];

        yield 'partner without insee - another insee excluded - same zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, null, true];
        yield 'partner without insee - another insee excluded - same zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneLaBodiniere, false]; // illogique, mais à tester, doit renvoyer false
        yield 'partner without insee - another insee excluded - same zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneLaBodiniere, $this->zoneBourgStMars, true];
        yield 'partner without insee - another insee excluded - no zone - no zone excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, null, false];
        yield 'partner without insee - another insee excluded - no zone - same zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, $this->zoneLaBodiniere, false];
        yield 'partner without insee - another insee excluded - no zone - different zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, null, $this->zoneBourgStMars, false];
        yield 'partner without insee - another insee excluded - different zone as geoloc, no zone excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaBodiniere, $this->zoneBourgStMars, null, false];
        yield 'partner without insee - another insee excluded - different zone as geoloc, same zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaTourmentinerie, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
        yield 'partner without insee - another insee excluded - different zone as geoloc, different zone as excluded' => [self::INSEE_STMARS, [], [self::INSEE_CELLIER], $this->geolocLaGree, $this->zoneBourgStMars, $this->zoneBourgStMars, false];
    }

    /**
     * @dataProvider provideRulesAndSignalementWithoutZone
     */
    public function testIsSatisfiedByWithoutZone(
        ?string $inseeSignalement,
        array $inseePartenaire,
        string $inseeToIncludeRule,
        ?array $inseeToExcludeRule,
        bool $isSatisfied,
    ): void {
        $partner = new Partner();
        $partner->setInsee($inseePartenaire);
        $this->assertEquals($inseePartenaire, $partner->getInsee());

        $signalement = new Signalement();
        $signalement->setInseeOccupant($inseeSignalement);
        $this->assertEquals($inseeSignalement, $signalement->getInseeOccupant());

        $specification = new CodeInseeSpecification($inseeToIncludeRule, $inseeToExcludeRule);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalementWithoutZone(): \Generator
    {
        yield 'all - same insee as partner - no exclude' => [self::INSEE_STMARS, [self::INSEE_STMARS], 'all', null, true];
        yield 'all - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], 'all', [self::INSEE_STMARS], false];
        yield 'all - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], 'all', [self::INSEE_CELLIER], true];
        yield 'all - different insee than partner - no exclude' => [self::INSEE_STMARS, [self::INSEE_CELLIER], 'all', null, true];
        yield 'all - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], 'all', [self::INSEE_STMARS], false];
        yield 'all - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], 'all', [self::INSEE_CELLIER], true];
        yield 'all - partner without insee - no exclude' => [self::INSEE_STMARS, [], 'all', null, true];
        yield 'all - partner without insee - but excluded' => [self::INSEE_STMARS, [], 'all', [self::INSEE_STMARS], false];
        yield 'all - partner without insee - another excluded' => [self::INSEE_STMARS, [], 'all', [self::INSEE_CELLIER], true];

        yield 'partner_list - same insee as partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_STMARS], 'partner_list', null, true];
        yield 'partner_list - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], 'partner_list', [self::INSEE_STMARS], false];
        yield 'partner_list - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], 'partner_list', [self::INSEE_CELLIER], true];
        yield 'partner_list - different insee than partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_CELLIER], 'partner_list', null, false];
        yield 'partner_list - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], 'partner_list', [self::INSEE_STMARS], false];
        yield 'partner_list - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], 'partner_list', [self::INSEE_CELLIER], false];
        yield 'partner_list - partner without insee - no exclusion' => [self::INSEE_STMARS, [], 'partner_list', null, false];
        yield 'partner_list - partner without insee - but excluded' => [self::INSEE_STMARS, [], 'partner_list', [self::INSEE_STMARS], false];
        yield 'partner_list - partner without insee - another excluded' => [self::INSEE_STMARS, [], 'partner_list', [self::INSEE_CELLIER], false];

        yield 'array of insee with this one - same insee as partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_STMARS, null, true];
        yield 'array of insee with this one - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_STMARS, [self::INSEE_STMARS], false];
        yield 'array of insee with this one - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_STMARS, [self::INSEE_CELLIER], true];
        yield 'array of insee with this one - different insee than partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_STMARS, null, true];
        yield 'array of insee with this one - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_STMARS, [self::INSEE_STMARS], false];
        yield 'array of insee with this one - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_STMARS, [self::INSEE_CELLIER], true];
        yield 'array of insee with this one - partner without insee - no exclusion' => [self::INSEE_STMARS, [], self::INSEE_STMARS, null, true];
        yield 'array of insee with this one - partner without insee - but excluded' => [self::INSEE_STMARS, [], self::INSEE_STMARS, [self::INSEE_STMARS], false];
        yield 'array of insee with this one - partner without insee - another excluded' => [self::INSEE_STMARS, [], self::INSEE_STMARS, [self::INSEE_CELLIER], true];

        yield 'array of insee without this one - same insee as partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_CELLIER, null, false];
        yield 'array of insee without this one - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_CELLIER, [self::INSEE_STMARS], false];
        yield 'array of insee without this one - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_CELLIER, [self::INSEE_CELLIER], false];
        yield 'array of insee without this one - different insee than partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_CELLIER, null, false];
        yield 'array of insee without this one - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_CELLIER, [self::INSEE_STMARS], false];
        yield 'array of insee without this one - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_CELLIER, [self::INSEE_CELLIER], false];
        yield 'array of insee without this one - partner without insee - no exclusion' => [self::INSEE_STMARS, [], self::INSEE_CELLIER, null, false];
        yield 'array of insee without this one - partner without insee - but excluded' => [self::INSEE_STMARS, [], self::INSEE_CELLIER, [self::INSEE_STMARS], false];
        yield 'array of insee without this one - partner without insee - another excluded' => [self::INSEE_STMARS, [], self::INSEE_CELLIER, [self::INSEE_CELLIER], false];

        // tous les cas ne sont pas testés en cas d'absence d'insee sur le signalement, car ça renvoie toujours false
        yield 'all - no insee signalement' => [null, [self::INSEE_CELLIER], 'all', [self::INSEE_CELLIER], false];
        yield 'partner_list - no insee signalement - another excluded' => [null, [], 'partner_list', [self::INSEE_CELLIER], false];
        yield 'array of insee - no insee signalement - another excluded' => [null, [], self::INSEE_STMARS, [self::INSEE_CELLIER], false];
    }
}
